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

    public function commitAndPush(string $path)
    {
        $this->git->addFile($path);
        $this->git->commit('update composer dependencies');
        return $this->git->push('origin', [$this->getCurrentBranchName()]);
    }

    public function getCurrentBranchName()
    {
        return $this->git->getCurrentBranchName();

    }
    protected function getBranchName()
    {
        return sprintf('update-composer-%s', date('Ymdhis'));
    }

}
