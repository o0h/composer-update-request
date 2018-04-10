# composer-update-request
Support to easily keep composer packages up-to-date, making pull request.
Try composer update and send pull request automatically.

[![Build Status](https://travis-ci.org/o0h/composer-update-request.svg?branch=master)](https://travis-ci.org/o0h/composer-update-request)
[![codecov](https://codecov.io/gh///branch/master/graph/badge.svg)](https://codecov.io/gh/o0h/composer-update-request)
[![MIT License](http://img.shields.io/badge/license-MIT-blue.svg?style=flat)](https://github.com/Connehito/cake-sentry/blob/master/LICENSE)

## Requirements
- PHP 7.0+

## Installation
```
composer global require o0h/composer-update-request
```

## Usage
### composer.json
You MUST set 'name' fieled in your composer.json.  
If not set, then update command run as `__root__` and skip create pull request.

### Set up env var.
Set up environment variables in bellow.

- GITHUB_TOKEN
    - github personal access token
    - see [setting page](https://github.com/settings/tokens)
- GITHUB_USER
    - user id of login to github(token owner)
    - like `o0h`
- GITHUB_REPOSITORY
    - repository name to send pull request
    - `org/repository` without `.git` suffix
    - like `o0h/composer-update-request-client-test-app`

### Set up scheduled task.
With cron jobs, auto checking composer-update and pull request will be created.
See `exsamples` directory.

### @TODO
This is still very WIP.
Following tasks must be done :muscle:

* write tests.
* phpstan with CI.
* support Circle CI.

