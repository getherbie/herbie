[![Packagist](https://img.shields.io/packagist/dt/getherbie/herbie.svg)](https://packagist.org/packages/getherbie/herbie)
[![GitHub (pre-)release](https://img.shields.io/github/release/getherbie/herbie/all.svg)](https://github.com/getherbie/herbie/releases)
[![License](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](https://github.com/getherbie/herbie/blob/master/LICENCE.md)
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

* [PSR-1](https://www.php-fig.org/psr/psr-1/) Basic Coding Standard
* [PSR-3](https://www.php-fig.org/psr/psr-3/) Logger Interface
* [PSR-4](https://www.php-fig.org/psr/psr-4/) Autoloading Standard
* [PSR-7](https://www.php-fig.org/psr/psr-7/) HTTP Message Interface
* [PSR-11](https://www.php-fig.org/psr/psr-11/) Container Interface
* [PSR-12](https://www.php-fig.org/psr/psr-12/) Extended Coding Style
* [PSR-14](https://www.php-fig.org/psr/psr-14/) Event Dispatcher
* [PSR-15](https://www.php-fig.org/psr/psr-15/) HTTP Handlers
* [PSR-16](https://www.php-fig.org/psr/psr-16/) Simple Cache
* [PSR-17](https://www.php-fig.org/psr/psr-17/) HTTP Factories

Herbie is well tested:

- Unit, Integration and Acceptance Tests with [Codeception](https://codeception.com)
- Static Code Analysis with [PHPStan](https://phpstan.org)
- Code Fixing with [PHP Coding Standards Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) 

## Use Cases

TBD

## Installation

### Composer 

The easiest way to install Herbie is through Composer.
Run the following commands in your terminal to create a new project and install all dependent libraries.

    composer create-project getherbie/start-website mywebsite

Change to the `mywebsite/web` directory:

    cd mywebsite/web

Start the internal webserver:
    
    php -S localhost:8888 index.php

Now open <http://localhost:8888> with your browser.
You should see your first Herbie website. 

## Development Environment

If you need a development environment, you can follow these steps.

Clone the GitHub repository.

    git clone https://github.com/getherbie/herbie.git

Change to the `herbie` directory.

    cd herbie

Install Composer dependencies.

    composer install

Change to the `website/web` directory.

    cd website/web/

Start PHP's internal web server.

    php -S localhost:9999 index.php

Now, open `localhost:9999` with your favorite web browser.

If you want to have additional console output or logging information, set the debug environment variable.

    HERBIE_DEBUG=1 php -S localhost:9999 index.php

If you want to use Xdebug (3.x), start the internal web server as follows.
Hint: For this to work, Xdebug must of course be installed.

    XDEBUG_MODE=debug php -S localhost:9999 index.php

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
