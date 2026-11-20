---
title: Homepage
hero:
  eyebrow: Flat-file CMS
  title: Websites built from text files.
  subtitle: Herbie is a blazing fast, lightweight, and highly customizable CMS running on PHP, Twig, Markdown, and YAML. No database required.
  primary_label: Get started
  primary_route: doc/first-steps/installation
  secondary_label: View on GitHub
  secondary_url: https://github.com/getherbie/herbie
  badges:
    - alt: Packagist downloads
      url: https://packagist.org/packages/getherbie/herbie
      image: https://img.shields.io/packagist/dt/getherbie/herbie.svg
    - alt: GitHub release
      url: https://github.com/getherbie/herbie/releases
      image: https://img.shields.io/github/release/getherbie/herbie/all.svg
    - alt: License BSD 3-Clause
      url: https://github.com/getherbie/herbie/blob/master/LICENCE.md
      image: https://img.shields.io/badge/License-BSD%203--Clause-blue.svg
    - alt: PHP from Packagist
      url: https://packagist.org/packages/getherbie/herbie
      image: https://img.shields.io/packagist/php-v/getherbie/herbie.svg
---

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
* Automated code style enforcement with [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer)

## Supported PHP Versions

8.3 / 8.4 / 8.5 / 8.6
