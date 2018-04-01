<?php

namespace O0h\ComposerUpdateRequest;

use Composer\Composer;
use Composer\Console\Application;

class ComposerService
{
    /** @var Composer */
    protected $composer;

    /** @var Application */
    protected $app;

    public function __construct()
    {
        var_dump(__FILE__);
        $this->app = new Application();
        $this->app->run();
        $composer = new Composer();
        $packages = $composer->getPackage();
    }
}
