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
    /** @var GitRepository */
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
        echo file_get_contents($path);
        var_dump(compact('path'));
        system('git status');
        $this->git->addFile($path);
        system('git status');
        $this->git->addAllChanges($path);
        system('git status');

        if (!$this->git->hasChanges()) {
            throw new \RuntimeException('Nothing to commit.');
        }

        $this->git->commit('update composer dependencies');
        $token = getenv('GITHUB_TOKEN');

        $remoteUrl = sprintf(
            'https://%s:%s@github.com/o0h/composer-update-request-test-app.git',
            'o0h',
            $token
        );
        $this->git->setRemoteUrl('origin', $remoteUrl);

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
