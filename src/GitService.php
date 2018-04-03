<?php
/**
 * Created by PhpStorm.
 * User: hkinjyo
 * Date: 2018/04/01
 * Time: 22:50
 */

namespace O0h\ComposerUpdateRequest;

use Cz\Git\GitRepository;

class GitService
{
    /** @var Repository */
    protected $git;

    public function __construct(string $directory)
    {
        $this->git = new GitRepository($directory);
    }

    public function createBranch()
    {
        $branchName = $this->getBranchName();
        $result = $this->git->createBranch($branchName, true);

        return $result;
    }

    public function commit(string $path)
    {
        $this->git->addFile($path);
        return $this->git->commit('update composer dependencies');
    }

    protected function getBranchName()
    {
        return sprintf('update-composer-%s', date('Ymdhis'));
    }

}
