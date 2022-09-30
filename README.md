[![Packagist](https://img.shields.io/packagist/dt/getherbie/herbie.svg)](https://packagist.org/packages/getherbie/herbie)
[![GitHub (pre-)release](https://img.shields.io/github/release/getherbie/herbie/all.svg)](https://github.com/getherbie/herbie/releases)
[![License](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](https://github.com/getherbie/herbie/blob/master/LICENCE.md)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/getherbie/herbie.svg)](https://packagist.org/packages/getherbie/herbie)


# :red_car: Herbie

Herbie is a simple flat-file Content Management System (CMS) based on human readable text files.

## About Herbie

With Herbie you are able to create a fully functional website or blog in a short amount of time and with little effort.

Herbie is based on proven libraries and concepts:

* [Markdown](https://www.markdownguide.org), [reStructuredText](https://docutils.sourceforge.io/rst.html) and [Textile](https://textile-lang.com) for formatting page content
* [Twig](https://twig.symfony.com) Template Engine for rendering layouts and extending Herbie's core
* [Yaml](http://www.yaml.org) and [JSON](https://www.json.org) for data structure files
* [Composer](http://getcomposer.org) and [Packagist](https://packagist.org) for Dependency and Plugin Management
* [Zend-EventManager](https://docs.zendframework.com/zend-eventmanager/)
* PHP Middlewares

Herbie supports the following PHP Standards Recommendations:

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


## Install

### Composer 

The easiest way to install Herbie is via Composer. 
To do this, execute the following commands in your terminal:

    # create project and install dependent libraries
    composer create-project getherbie/start-website myproject
    
    # go to web directory
    cd myproject/web
    
    # start internal webserver 
    php -S localhost:8888 index.php

Now, open <http://localhost:8888> in your browser.
You should see your first Herbie website. 

### Docker

Or you can achieve the same by using Docker.

    docker run --rm -it -v $PWD:/app composer create-project --ignore-platform-reqs getherbie/start-website myproject 
    cd myproject


## Development Environment

If you need a development environment, you can follow the steps below.

Clone the git repository.

    git clone https://github.com/getherbie/herbie.git

Change to the `herbie` directory.

    cd herbie

Install Composer dependencies.

    composer install

Start PHP's internal web server.

    php -S localhost:9999 -t example/web

Or, if you want to use Xdebug (3.x), start the internal web server as follows.
Hint: For this to work, Xdebug must of course be installed.

    export XDEBUG_MODE=debug; php -S localhost:9999 -t example/web

Now, open `localhost:9999` with your favorite web browser.


## Tests

Run acceptance tests

    php vendor/bin/codecept run acceptance

Run unit tests

    php vendor/bin/codecept run unit

Run integration tests

    php vendor/bin/codecept run integration

Run all tests

    php vendor/bin/codecept run


## More Information

More information see <https://herbie.tebe.ch>.
