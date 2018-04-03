<?php

namespace O0h\ComposerUpdateRequest;

use Composer\Composer;
use Composer\Factory as ComposerFactory;
use Composer\IO\IOInterface;
use Composer\Script\ScriptEvents;
use Composer\Script\Event;
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
    }

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

    public function onPreUpdate(Event $arg)
    {
        $this->before = $this->getLocalPackages();
    }

    public function onPostUpdate(Event $arg)
    {
        $packages = $this->getLocalPackages();
        $diff = array_diff_assoc($packages, $this->before);
        if (!$diff) {
            // return;
        }
        $pjRoot = $this->getPjRoot();
        $git = new GitService($pjRoot);
        $r = $git->createBranch();
        $composerFile = ComposerFactory::getComposerFile();
        $lockFile = substr($composerFile, 0,  '-4') . 'lock';
        $git->commit($lockFile);
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
}