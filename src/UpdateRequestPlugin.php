<?php

namespace O0h\ComposerUpdateRequest;

use Composer\Composer;
use Composer\Factory as ComposerFactory;
use Composer\IO\IOInterface;
use Composer\Repository\BaseRepository;
use Composer\Script\ScriptEvents; use Composer\Script\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\PluginInterface;

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

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->includeGuzzleFunctions();
    }

    private function includeGuzzleFunctions()
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        require_once "{$vendorDir}/guzzlehttp/guzzle/src/functions_include.php";
        require_once "{$vendorDir}/guzzlehttp/psr7/src/functions_include.php";
        require_once "{$vendorDir}/guzzlehttp/promises/src/functions_include.php";
    }

    public static function getSubscribedEvents()
    {
        if (!getenv('ENABLE_AUTO_CREATE_COMPOSER_UPDATE_REQUEST')) {
            return [];
        }

        return [
            ScriptEvents::PRE_UPDATE_CMD => [
                'onPreUpdate',
            ],
            ScriptEvents::POST_UPDATE_CMD => [
                'onPostUpdate',
            ],
        ];
    }

    public function onPreUpdate(Event $arg)
    {
        $this->before = $this->getLocalPackages();
    }

    public function onPostUpdate(Event $arg)
    {
        $packages = $this->getLocalPackages();
        $diff = array_diff_assoc($packages, $this->before);
        if (!$diff) {
            return true;
        }

        $this->io->write('Starting to create composer-update pull request!');
        $pjRoot = $this->getPjRoot();
        $git = new GitService($pjRoot);
        $r = $git->createBranch();
        $composerFile = ComposerFactory::getComposerFile();
        $lockFile = substr($composerFile, 0,  '-4') . 'lock';
        $git->commitAndPush($lockFile);

        $hub = new GithubService();
        $title = $this->generatePullRequestTitle();
        $body = $this->generatePullRequestBody($diff);
        $result = $hub->createPullRequest($title, $body, $git->getCurrentBranchName());

        $this->io->write('Complete!');
        $this->io->write('Check the request in ' . $result['html_url']);
    }

    protected function getLocalPackages()
    {
        $repo = $this->composer->getRepositoryManager()->getLocalRepository();
        $localPackages = [];
        foreach ($repo->getPackages() as $package) {
            $localPackages[$package->getName()] = $package->getVersion();
        }

        return $localPackages;
    }

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

    protected function generatePullRequestTitle()
    {
        return sprintf('PHP dependencies update.(%d)', date('Ymd'));
    }

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
                implode(',' , array_keys($why)),
                $this->before[$name] ?? '--',
                $ver
            );

        }

        return $content;
    }

}