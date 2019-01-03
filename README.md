# :red_car: Herbie

Herbie is a simple Flat-File CMS- und Blogsystem based on human readable text files.

## About Herbie

With Herbie you are able to create a full functional website or blog in a short amount of time and with little effort.

Herbie is based on the following libraries:

* [Markdown][markdown] and [Textile][textile] for formatting page content
* [Twig][twig] Template Engine for rendering layouts
* [Yaml][yaml] for configuration and data structures files
* [Pimple][pimple] as Dependency Injection Container
* [Composer][composer] and [Packagist][packagist] for das Dependency and Plugin Management

Herbie supports the following [PHP Standards Recommendations][psr]:

* 2 Coding Style Guid 
* 3	Logger Interface
* 4	Autoloading Standard
* 7 HTTP Message Interface
* 11 Container Interface
* 15 HTTP Handlers
* 16 Simple Cache
* 17 HTTP Factories

 
## Install

The easiest way to install Herbie is via Composer. To do this, execute the following statement in the terminal:

    $ composer create-project getherbie/start-website myproject

Composer creates your website in the `myproject` directory and installs all dependent libraries.

Go to the web directory and start the built-in web server of PHP.

    $ cd myproject/web
    $ php -S localhost:8888

Visit the website in the browser at <http://localhost:8888>. Finished!


## More Information

More information see [www.getherbie.org][herbie].


[markdown]: https://www.markdownguide.org
[textile]: https://textile-lang.com
[twig]: http://twig.sensiolabs.org
[yaml]: http://www.yaml.org
[pimple]: http://pimple.sensiolabs.org
[composer]: http://getcomposer.org
[packagist]: https://packagist.org
[symfony]: http://symfony.com/doc/current/components/
[psr]: https://www.php-fig.org/psr/
[herbie]: https://www.getherbie.org
