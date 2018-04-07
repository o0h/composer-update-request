<?php
/**
 * Created by PhpStorm.
 * User: hkinjyo
 * Date: 2018/04/01
 * Time: 22:51
 */

namespace O0h\ComposerUpdateRequest;

use Github\Client;
use Github\HttpClient\Builder;

class GithubService
{
    /** @var Client */
    private $hub;

    public function __construct()
    {
        $builder = new Builder();
        $github = new Client($builder, 'composer-update-request-client');
        $this->user = getenv('GITHUB_USER');
        $this->token = getenv('GITHUB_TOKEN');
        $github->authenticate($this->user, $this->token, \Github\Client::AUTH_HTTP_PASSWORD);
        $this->hub = $github;

    }

    public function createPullRequest($title, $content, $branch)
    {
        return $this->hub->api('pull_request')->create($this->user, 'composer-update-request-test-app', [
            'base'  => 'master',
            'head'  => $branch,
            'title' => $title,
            'body'  => $content,
        ]);
    }



}