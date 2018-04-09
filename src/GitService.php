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

    public function hasChanges($path)
    {
        $executed = implode($this->git->execute(['status', $path]));
        $hasChanged = strpos(
            $executed,
            'nothing to commit.'
        ) === false;

        var_dump(compact('executed', 'hasChanged'));
        return $hasChanged;
    }

    public function commitAndPush(string $commitFile)
    {
        $this->git->addFile($commitFile);

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
