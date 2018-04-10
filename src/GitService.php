<?php
namespace O0h\ComposerUpdateRequest;

use Cz\Git\GitRepository;

/**
 * Class GitService
 *
 * For managing the git commands
 */
class GitService
{
    /** @var GitRepository */
    protected $git;

    /**
     * GitService constructor.
     * @param string $directory git root path
     */
    public function __construct(string $directory)
    {
        $this->git = new GitRepository($directory);
    }

    /**
     * Create and checkout new branch
     *
     * @return GitRepository
     */
    public function createBranch()
    {
        $branchName = $this->getBranchName();
        $result = $this->git->createBranch($branchName, true);

        return $result;
    }

    /**
     * Check if passed file is modified
     *
     * @param string $path path for checking file
     * @return bool
     */
    public function hasChanges(string $path)
    {
        $executed = implode($this->git->execute(['status', '-s', $path]));
        $hasChanged = strpos($executed, basename($path)) > 0;

        return $hasChanged;
    }

    /**
     * Add file to git and commit, push to remote
     *
     * @param string $commitFile
     * @return GitRepository
     */
    public function commitAndPush(string $commitFile)
    {
        $this->git->setRemoteUrl('origin', $this->getRemoteUrl());

        $this->git->addFile($commitFile);
        $this->git->commit('Update composer dependencies');

        return $this->git->push('origin', [$this->getCurrentBranchName()]);
    }

    /**
     * Get current branch name
     *
     * @return string
     */
    public function getCurrentBranchName()
    {
        return $this->git->getCurrentBranchName();
    }

    /**
     * Get new branch name to check out
     *
     * @return string
     */
    protected function getBranchName()
    {
        return sprintf('update-composer-%s', date('Ymdhis'));
    }

    /**
     * Get git remote url
     *
     * If env `GIT_REMOTE_URL` is set, return that.
     * Else, generate remote basic-auth url with github-user-name, token and repository
     *
     * @return string
     */
    protected function getRemoteUrl()
    {
        $env = getenv('GIT_REMOTE_URL');
        if ($env) {
            return $env;
        }

        $user = getenv('GITHUB_USER');
        $token = getenv('GITHUB_TOKEN');
        $repo = getenv('GITHUB_REPOSITORY');
        $remoteUrl = sprintf(
            'https://%s:%s@github.com/%s.git',
            $user,
            $token,
            $repo
        );

        return $remoteUrl;
    }
}
