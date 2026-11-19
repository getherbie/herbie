[![Packagist](https://img.shields.io/packagist/dt/getherbie/herbie.svg)](https://packagist.org/packages/getherbie/herbie)
[![GitHub (pre-)release](https://img.shields.io/github/release/getherbie/herbie/all.svg)](https://github.com/getherbie/herbie/releases)
[![License](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](https://github.com/getherbie/herbie/blob/3.x/LICENCE.md)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/getherbie/herbie.svg)](https://packagist.org/packages/getherbie/herbie)

# Herbie

> Create <u>small</u> but fully functional websites or blogs in no time and with little effort 👌

Herbie is a simple, modern, fast and highly customizable flat-file Content Management System (CMS) powered by PHP, Twig, Markdown, Textile, reStructuredText and other human-readable text files.

## Featuring

Herbie is powered by proven libraries:

* [Markdown](https://www.markdownguide.org), [reStructuredText](https://docutils.sourceforge.io/rst.html) and [Textile](https://textile-lang.com) for formatting page content
* [Twig](https://twig.symfony.com) template engine for rendering layouts and extending the core
* [Yaml](http://www.yaml.org) and [JSON](https://www.json.org) for data structure files
* [Composer](http://getcomposer.org) and [Packagist](https://packagist.org) for Dependency and Plugin Management

Thanks to its plugin system Herbie is highly customizable and brings support for:

* Application and Route Middlewares
* Event Listeners
* Twig Filters, Twig Globals, Twig Functions and Twig Tests
* Symfony Console Commands

Herbie implements the following PHP standard recommendations:

* [PSR-3](https://www.php-fig.org/psr/psr-3/) Logger Interface
* [PSR-4](https://www.php-fig.org/psr/psr-4/) Autoloading Standard
* [PSR-7](https://www.php-fig.org/psr/psr-7/) HTTP Message Interface
* [PSR-11](https://www.php-fig.org/psr/psr-11/) Container Interface
* [PSR-14](https://www.php-fig.org/psr/psr-14/) Event Dispatcher
* [PSR-15](https://www.php-fig.org/psr/psr-15/) HTTP Handlers
* [PSR-16](https://www.php-fig.org/psr/psr-16/) Simple Cache
* [PSR-17](https://www.php-fig.org/psr/psr-17/) HTTP Factories
* [PER-CS 3.0](https://www.php-fig.org/per/coding-style/) Coding Style

Herbie is well tested:

- Unit, Integration and Acceptance Tests with [Codeception](https://codeception.com)
- Static Code Analysis with [PHPStan](https://phpstan.org)
- Code Fixing with [PHP Coding Standards Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer)

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
    docker compose run --rm test       # run test suite
    docker compose up test-website     # start test suite website
    docker compose run --rm bash       # open a shell

Use a different PHP version (default: 8.3):

    PHP_VERSION=8.3 docker compose up website
    PHP_VERSION=8.4 docker compose up website
    PHP_VERSION=8.5 docker compose up website
    PHP_VERSION=8.6 docker compose up website

Run specific test suites or coverage inside a container:

    docker compose run --rm bash

Then run:

    php vendor/bin/codecept run
    php vendor/bin/codecept run unit
    php vendor/bin/codecept run integration
    php vendor/bin/codecept run acceptance
    XDEBUG_MODE=coverage php vendor/bin/codecept run --coverage --coverage-xml --coverage-html

<details>
<summary>Without Docker (PHP & Composer required)</summary>

    git clone https://github.com/getherbie/herbie.git
    cd herbie
    composer install
    cd website/web
    php -S localhost:8888 index.php

Optional: `HERBIE_DEBUG=1 php -S localhost:8888 index.php` for debug output, or `XDEBUG_MODE=debug php -S localhost:8888 index.php` for Xdebug.

Run tests:

    php vendor/bin/codecept run
    php vendor/bin/codecept run unit
    php vendor/bin/codecept run integration
    php vendor/bin/codecept run acceptance
    XDEBUG_MODE=coverage php vendor/bin/codecept run --coverage --coverage-xml --coverage-html

</details>

## More Information

For more information, see <https://herbie.tebe.ch>.
