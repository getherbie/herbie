[![Packagist](https://img.shields.io/packagist/dt/getherbie/herbie.svg)](https://packagist.org/packages/getherbie/herbie)
[![GitHub (pre-)release](https://img.shields.io/github/release/getherbie/herbie/all.svg)](https://github.com/getherbie/herbie/releases)
[![License](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](https://github.com/getherbie/herbie/blob/3.x/LICENCE.md)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/getherbie/herbie.svg)](https://packagist.org/packages/getherbie/herbie)

# Herbie

> Create <u>small</u> but fully functional websites or blogs in no time and with little effort 👌

Herbie is a blazing fast, lightweight, and highly customizable flat-file Content Management System (CMS) running on PHP, Twig, Markdown, Textile, reStructuredText, and other human-readable text files.

## What Herbie Is Built On

Herbie relies on battle-tested libraries:

* [Markdown](https://www.markdownguide.org), [reStructuredText](https://docutils.sourceforge.io/rst.html), and [Textile](https://textile-lang.com) for page content formatting
* [Twig](https://twig.symfony.com) as the template engine for layouts and core extensibility
* [YAML](http://www.yaml.org) and [JSON](https://www.json.org) for structured data
* [Composer](https://getcomposer.org) and [Packagist](https://packagist.org) for dependency and plugin management

Its plugin system makes Herbie highly extensible, with built-in support for:

* Application and route middlewares
* Event listeners
* Custom Twig filters, functions, tests, and globals
* Console commands

Herbie follows the PHP-FIG standards:

* [PSR-3](https://www.php-fig.org/psr/psr-3/) Logger Interface
* [PSR-4](https://www.php-fig.org/psr/psr-4/) Autoloading Standard
* [PSR-7](https://www.php-fig.org/psr/psr-7/) HTTP Message Interface
* [PSR-11](https://www.php-fig.org/psr/psr-11/) Container Interface
* [PSR-14](https://www.php-fig.org/psr/psr-14/) Event Dispatcher
* [PSR-15](https://www.php-fig.org/psr/psr-15/) HTTP Handlers
* [PSR-16](https://www.php-fig.org/psr/psr-16/) Simple Cache
* [PSR-17](https://www.php-fig.org/psr/psr-17/) HTTP Factories
* [PER-CS 3.0](https://www.php-fig.org/per/coding-style/) Coding Style

Quality is ensured through:

* Unit, integration, and acceptance testing with [Codeception](https://codeception.com)
* Static analysis with [PHPStan](https://phpstan.org)
* Automated code style enforcement with [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) and [Twig-CS-Fixer](https://github.com/vincentlanglet/twig-cs-fixer)
* Static architecture analysis with [Deptrac](https://github.com/deptrac/deptrac)

## Supported PHP Versions

8.3 / 8.4 / 8.5 / 8.6

## Quick Start

Create your own Herbie website with Docker (recommended):

    docker run --rm -v "$PWD":/app composer create-project --ignore-platform-reqs getherbie/start-website mywebsite
    cd mywebsite
    docker compose up website

Open <http://localhost:8888>.

<details>
<summary>Without Docker (PHP & Composer required)</summary>

    composer create-project getherbie/start-website mywebsite
    cd mywebsite/web
    php -S localhost:8888 index.php

</details>

## Development

Work on this repository with Docker (recommended):

    git clone https://github.com/getherbie/herbie.git
    cd herbie
    docker compose up website

Open <http://localhost:8888>.

Common tasks:

    docker compose run --rm install    # install Composer dependencies
    docker compose run --rm qa         # run all checks and tests
    docker compose run --rm bash       # open a shell
    docker compose up test-website     # test fixture site on http://localhost:8889

Use a different PHP version (default: 8.3):

    PHP_VERSION=8.3 docker compose up website
    PHP_VERSION=8.4 docker compose up website
    PHP_VERSION=8.5 docker compose up website
    PHP_VERSION=8.6 docker compose up website

Checks and tests (inside the container via `docker compose run --rm bash`, or with local PHP/Composer):

    composer qa                  # all checks and all test suites

Static checks only:

    composer qa:check            # lint, cs, cs-twig, phpstan, deptrac
    composer qa:check:phpstan    # or a single check
    composer qa:check:deptrac
    composer qa:check:lint
    composer qa:check:cs
    composer qa:check:cs-twig

Tests only:

    composer qa:test             # all suites
    composer qa:test:unit        # or a single suite
    composer qa:test:integration
    composer qa:test:acceptance
    composer qa:test:website
    composer qa:test:coverage

Auto-fix code style:

    composer qa:fix              # PHP and Twig
    composer qa:fix:cs           # PHP only
    composer qa:fix:cs-twig      # Twig only

Coverage requires Xdebug or PCOV. The Docker image includes Xdebug, so `composer qa:test:coverage` works inside the container; for a local PHP installation you need to install a coverage driver first.

<details>
<summary>Without Docker (PHP & Composer required)</summary>

    git clone https://github.com/getherbie/herbie.git
    cd herbie
    composer install
    cd website/web
    php -S localhost:8888 index.php

Optional: `HERBIE_DEBUG=1 php -S localhost:8888 index.php` for debug output, or `XDEBUG_MODE=debug php -S localhost:8888 index.php` for Xdebug.

</details>

## More Information

For more information, see <https://herbie.tebe.ch>.
