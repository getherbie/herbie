[![Packagist](https://img.shields.io/packagist/dt/getherbie/herbie.svg)](https://packagist.org/packages/getherbie/herbie)
[![GitHub (pre-)release](https://img.shields.io/github/release/getherbie/herbie/all.svg)](https://github.com/getherbie/herbie/releases)
[![License](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](https://github.com/getherbie/herbie/blob/master/LICENCE.md)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/getherbie/herbie.svg)](https://packagist.org/packages/getherbie/herbie)


# :red_car: Herbie

Herbie is a simple Flat-File CMS- und Blogsystem based on human readable text files.


## About Herbie

With Herbie you are able to create a fully functional website or blog in a short amount of time and with little effort.

Herbie is based on proven libraries and concepts:

* [Markdown](https://www.markdownguide.org) and [Textile](https://textile-lang.com) for formatting page content
* [Twig](http://twig.sensiolabs.org) Template Engine for rendering layouts
* [Yaml](http://www.yaml.org) for configuration and data structures files
* [Composer](http://getcomposer.org) and [Packagist](https://packagist.org) for das Dependency and Plugin Management
* [Zend-EventManager](https://docs.zendframework.com/zend-eventmanager/)
* PHP Middlewares

Herbie supports the following [PHP Standards Recommendations](https://www.php-fig.org/psr/):

* PSR-2  Coding Style Guide
* PSR-3  Logger Interface
* PSR-4  Autoloading Standard
* PSR-7  HTTP Message Interface
* PSR-11 Container Interface
* PSR-15 HTTP Handlers
* PSR-16 Simple Cache
* PSR-17 HTTP Factories


## Install

The easiest way to install Herbie is via Composer. To do this, execute the following statement in your terminal:

    $ composer create-project getherbie/start-website:dev-master myproject

Composer creates your website in the `myproject` folder and installs all dependent libraries.

Go to the web directory and start PHPs built-in web server.

    $ cd myproject/web
    $ php -S localhost:8888 index.php

Now, open <http://localhost:8888> in your browser. You should then see your first Herbie website. 


## More Information

More information see [www.getherbie.org](https://www.getherbie.org).
