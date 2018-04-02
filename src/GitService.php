<?php
/**
 * Created by PhpStorm.
 * User: hkinjyo
 * Date: 2018/04/01
 * Time: 22:50
 */

namespace O0h\ComposerUpdateRequest;

use TQ\Git\Repository\Repository;
use TQ\Vcs\Cli\CallResult;
use TQ\Vcs\Repository\Transaction;
use GitWrapper\GitWrapper;

class GitService
{
    /** @var Repository */
    protected $git;

    public function __construct(string $directory)
    {
        $this->git = Repository::open($directory);
    }

    public function createBranch()
    {
        $branchName = $this->getBranchName();
        $this->git->getGit();
        /** @var $result CallResult */
        $result = $this->git->getGit()->{'checkout'}($this->git->getRepositoryPath(), [
            '-b',
            $branchName
        ]);
        $result->assertSuccess('Failed to create new branch. ' . $result->getStdErr());

        return $result;
    }

    public function commitAll()
    {
        $result = $this->git->transactional(function(Transaction $t) {
            $t->setCommitMsg('the commit is aaa..');
        });

        return $result;
    }

    protected function getBranchName()
    {
        return sprintf('update-composer-%s', date('Ymdhis'));
    }

}
