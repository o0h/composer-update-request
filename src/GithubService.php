<?php

namespace O0h\ComposerUpdateRequest;

use Github\Client;
use Github\HttpClient\Builder;

class GithubService
{
    /** @var Client */
    protected $hub;

    /** @var string Github repository org(or owner) name */
    protected $org;

    /** @var string Github repository name */
    protected $repo;

    /**
     * GithubService constructor.
     */
    public function __construct()
    {
        list($org, $repo) = explode('/', getenv('GITHUB_REPOSITORY'));
        $this->org = $org;
        $this->repo = $repo;

        $this->setClient();
        $this->setAuth();
    }

    /**
     * Send Github pull request.
     *
     * @param string $title pull request title
     * @param string $content pull request description
     * @param string $branch branch merge request from
     * @return mixed
     */
    public function createPullRequest(string $title, string $content, string $branch)
    {
        return $this->hub->api('pull_request')->create($this->org, $this->repo, [
            'base'  => getenv('GITHUB_BASE_BRANCH') ?: 'master',
            'head'  => $branch,
            'title' => $title,
            'body'  => $content,
        ]);
    }

    /**
     * Setup Github Client
     */
    protected function setClient()
    {
        $builder = new Builder();
        $this->hub = new Client($builder, $this->repo);
    }

    /**
     * Set Github user authentication
     */
    protected function setAuth()
    {
        $user = getenv('GITHUB_USER');
        $token = getenv('GITHUB_TOKEN');
        $this->hub->authenticate($user, $token, \Github\Client::AUTH_HTTP_PASSWORD);
    }
}
