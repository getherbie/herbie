[![Packagist](https://img.shields.io/packagist/dt/getherbie/herbie.svg)](https://packagist.org/packages/getherbie/herbie)
[![GitHub (pre-)release](https://img.shields.io/github/release/getherbie/herbie/all.svg)](https://github.com/getherbie/herbie/releases)
[![License](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](https://github.com/getherbie/herbie/blob/master/LICENCE.md)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/getherbie/herbie.svg)](https://packagist.org/packages/getherbie/herbie)

# Herbie CMS

> Create <u>small</u> but fully functional websites or blogs in no time and with little effort 👌

Herbie CMS is a simple, modern, fast and highly customizable flat-file Content Management System powered by PHP, Twig, Markdown, Textile, reStructuredText and other human-readable text files.

## Featuring

Herbie CMS is powered by proven libraries:

* [Markdown](https://www.markdownguide.org), [reStructuredText](https://docutils.sourceforge.io/rst.html) and [Textile](https://textile-lang.com) for formatting page content
* [Twig](https://twig.symfony.com) Template Engine for rendering layouts and extending Herbie CMS's core
* [Yaml](http://www.yaml.org) and [JSON](https://www.json.org) for data structure files
* [Composer](http://getcomposer.org) and [Packagist](https://packagist.org) for Dependency and Plugin Management

Thanks to its plugin system Herbie CMS is highly customizable and brings support for:

* Application and Route Middlewares
* Event Handlers and Intercepting Filters
* Twig Filters, Twig Globals, Twig Functions and Twig Tests
* Symfony Console Commands

Herbie CMS implements the following PHP standard recommendations:

* [PSR-1](https://www.php-fig.org/psr/psr-1/) Basic Coding Standard
* [PSR-2](https://www.php-fig.org/psr/psr-2/) Coding Style Guide
* [PSR-3](https://www.php-fig.org/psr/psr-3/) Logger Interface
* [PSR-4](https://www.php-fig.org/psr/psr-4/) Autoloading Standard
* [PSR-7](https://www.php-fig.org/psr/psr-7/) HTTP Message Interface
* [PSR-11](https://www.php-fig.org/psr/psr-11/) Container Interface
* [PSR-12](https://www.php-fig.org/psr/psr-12/) Extended Coding Style
* [PSR-15](https://www.php-fig.org/psr/psr-15/) HTTP Handlers
* [PSR-16](https://www.php-fig.org/psr/psr-16/) Simple Cache
* [PSR-17](https://www.php-fig.org/psr/psr-17/) HTTP Factories

Herbie CMS is well tested:

- Unit, Integration and Acceptance Tests with [Codeception](https://codeception.com)
- Static Code Analysis with [PHPStan](https://phpstan.org)
- Code Fixing with [PHP Coding Standards Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) 

## Use Cases

TBD

## Installation

### Composer 

The easiest way to install Herbie CMS is with Composer.
To do this, run the following commands in your terminal:

For the upcoming version 2.x:

    # create project and install dependent libraries
    composer create-project getherbie/start-website:dev-master mywebsite

For the stable version 1.x:

    # create project and install dependent libraries
    composer create-project getherbie/start-website mywebsite

Start the internal webserver:

    # go to the web directory
    cd mywebsite/web
    
    # start the internal webserver 
    php -S localhost:8888 index.php

Now open <http://localhost:8888> with your browser.
You should see your first Herbie CMS website. 

### Docker

You can achieve the same by using Docker.

    docker run --rm -it -v $PWD:/app composer create-project --ignore-platform-reqs getherbie/start-website myproject 
    cd myproject

## Development Environment

If you need a development environment, you can follow these steps.

Clone the GitHub repository.

    git clone https://github.com/getherbie/herbie.git

Change to the `herbie` directory.

    cd herbie

Install Composer dependencies.

    composer install

Start PHP's internal web server.

    php -S localhost:9999 example/web/index.php

Now, open `localhost:9999` with your favorite web browser.

If you want to have additional console output or logging information, set Herbie CMS's debug environment variable.

    HERBIE_DEBUG=1 php -S localhost:9999 example/web/index.php

If you want to use Xdebug (3.x), start the internal web server as follows.
Hint: For this to work, Xdebug must of course be installed.

    XDEBUG_MODE=debug php -S localhost:9999 example/web/index.php

## Tests

Run unit tests

    php vendor/bin/codecept run unit

Run integration tests

    php vendor/bin/codecept run integration

Run acceptance tests

    php vendor/bin/codecept run acceptance

Run all tests

    php vendor/bin/codecept run

Run tests with Code Coverage

    XDEBUG_MODE=coverage vendor/bin/codecept run --coverage --coverage-xml --coverage-html

## More Information

For more information, see <https://herbie.tebe.ch>.
