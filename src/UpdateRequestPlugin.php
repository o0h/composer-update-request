<?php

namespace O0h\ComposerUpdateRequest;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory as ComposerFactory;
use Composer\IO\IOInterface;
use Composer\Repository\BaseRepository;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class UpdateRequestPlugin implements PluginInterface, EventSubscriberInterface
{
    /** @var Composer */
    protected $composer;

    /** @var IOInterface */
    protected $io;

    /** @var array packages before updating */
    protected $before = [];

    /** @var array packages after updating */
    protected $after = [];

    /** @var bool check composer-update run in COMPOSER_HOME */
    protected $onRoot = false;

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        if ($composer->getPackage()->getName() === '__root__') {
            $io->write('Called in root, skip composer update auto request.');
            $this->onRoot = true;
        }

        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Include guzzle functions for lacking of autoload-files
     */
    private function includeGuzzleFunctions()
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        require_once "{$vendorDir}/guzzlehttp/guzzle/src/functions_include.php";
        require_once "{$vendorDir}/guzzlehttp/psr7/src/functions_include.php";
        require_once "{$vendorDir}/guzzlehttp/promises/src/functions_include.php";
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     * * The method name to call (priority defaults to 0)
     * * An array composed of the method name to call and the priority
     * * An array of arrays composed of the method names to call and respective
     *   priorities, or 0 if unset
     *
     * For instance:
     *
     * * array('eventName' => 'methodName')
     * * array('eventName' => array('methodName', $priority))
     * * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::PRE_UPDATE_CMD => [
                'onPreUpdate',
            ],
            ScriptEvents::POST_UPDATE_CMD => [
                'onPostUpdate',
            ],
        ];
    }

    /**
     * Pre-updating hook.
     *
     * Get before-updating packages.
     *
     * @param Event $arg
     * @return bool
     */
    public function onPreUpdate(Event $arg)
    {
        if ($this->onRoot) {
            return true;
        }
        $this->before = $this->getLocalPackages();

        return true;
    }

    /**
     * Pre-updating hook.
     *
     * Get before-updating packages.
     *
     * @param Event $arg
     * @return bool all true
     * @throws \RuntimeException Detected mismatches between .lock and local packages.
     */
    public function onPostUpdate(Event $arg)
    {
        if ($this->onRoot) {
            return true;
        }

        $composerFile = ComposerFactory::getComposerFile();
        chdir(dirname($composerFile));

        $lockFile = substr($composerFile, 0, '-4') . 'lock';

        $this->includeGuzzleFunctions();
        $pjRoot = $this->getPjRoot();
        $git = new GitService($pjRoot);

        if (!$git->hasChanges($lockFile)) {
            $this->io->write('.lock file has no changes.');

            return true;
        }

        $packages = $this->getLocalPackages();
        $diff = array_diff_assoc($packages, $this->before);
        if (!$diff) {
            throw new \RuntimeException('composer.lock is modified but local packages have no diff!');
        }

        $this->io->write('Starting to create composer-update pull request!');
        $git->createBranch();
        $git->commitAndPush($lockFile);

        $hub = new GithubService();
        $title = $this->generatePullRequestTitle();
        $body = $this->generatePullRequestBody($diff);
        $result = $hub->createPullRequest($title, $body, $git->getCurrentBranchName());

        $this->io->write('Complete!');
        $this->io->write('Check the request in ' . $result['html_url']);

        return true;
    }

    /**
     * Get local packages and each version hash
     *
     * @return array {PackageName:Version} assoc
     */
    protected function getLocalPackages()
    {
        $repo = $this->composer->getRepositoryManager()->getLocalRepository();
        $localPackages = [];
        foreach ($repo->getPackages() as $package) {
            $localPackages[$package->getName()] = $package->getVersion();
        }

        return $localPackages;
    }

    /**
     * Get git project root path
     *
     * @return string absolute path for directory set .git
     */
    protected function getPjRoot()
    {
        $dir = getcwd();
        while (true) {
            $path = $dir . DIRECTORY_SEPARATOR . '.git';
            if (file_exists($path) && is_dir($path)) {
                break;
            }
            $dir .= DIRECTORY_SEPARATOR . '..';
        }
        $path = dirname(realpath($path)) . DIRECTORY_SEPARATOR;

        return $path;
    }

    /**
     * Generate github pull request title
     *
     * @return string
     */
    protected function generatePullRequestTitle()
    {
        return sprintf('PHP dependencies update.(%d)', date('Ymd'));
    }

    /**
     * Generate github pull request description (with PR template)
     *
     * @return string
     */
    protected function generatePullRequestBody(array $diff)
    {
        $templatePath = $this->getPjRoot() . 'PULL_REQUEST_TEMPLATE/composer_update.md';
        if (file_exists($templatePath)) {
            $content = file_get_contents($templatePath)
                . PHP_EOL
                . PHP_EOL
                . '----'
                . PHP_EOL;
        } else {
            $content = 'PHP dependencies update.' . PHP_EOL;
        }
        $content .= 'The bellow packages will be updated.'
            . PHP_EOL
            . PHP_EOL
            . '| package | required by | before | current |'
            . PHP_EOL
            . '| ---- | ---- | ---- | ---- |'
            . PHP_EOL;

        /** @var BaseRepository $repository */
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();
        foreach ($diff as $name => $ver) {
            $why = $repository->getDependents($name);
            $content .= sprintf(
                '| %s | %s | %s | %s |' . PHP_EOL,
                $name,
                implode("<br>", array_keys($why)),
                $this->before[$name] ?? '--',
                $ver
            );
        }

        return $content;
    }
}
