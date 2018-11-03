# :red_car: Herbie

Herbie ist ein einfaches Flat-File CMS- und Blogsystem, das auf simplen Textdateien basiert.

## Was ist Herbie?

Mit Herbie erstellst du mit einfachen Markdown- und Textiledateien in kurzer Zeit und mit wenig Aufwand eine voll
funktionsfähige Website oder einen Blog.

Herbie baut auf bewährten Bibliotheken und Komponenten auf.

* [Markdown][markdown] und [Textile][textile] zur Formatierung von Inhalten
* [Twig][twig] Template Engine zur Erstellung von Layouts
* [Yaml][yaml] zum Konfigurieren der Website und Verwalten von Datenstrukturen
* [Pimple][pimple] als Dependency Injection Container
* [Composer][composer] und [Packagist][packagist] für das Dependency Management
* die HttpFoundation-, EventDispatcher- und Yaml-Komponenten der [Symfony Components][symfony]
* [Imagine][imagine] zur Bildmanipulation und -bearbeitung
* [GeSHi][geshi] als leistungsfähgier Code Syntaxhighlighter

## Installation

Am einfachsten installierst du Herbie via Composer. Führe dazu im Terminal die folgende Anweisung aus:

    $ composer create-project getherbie/start-website myproject

Composer erstellt im Verzeichnis `myproject` deine Website und installiert alle abhängigen Bibliotheken.

Danach wechselst du ins Webverzeichnis und startest den eingebauten Webserver von PHP. 

    $ cd myproject/web
    $ php -S localhost:8888

Dann musst du nur noch die Website im Browser unter <http://localhost:8888> aufrufen. Fertig!

## Website

Weitere Informationen findest du unter [www.getherbie.org](https://www.getherbie.org).


[markdown]: http://daringfireball.net/projects/markdown/
[textile]: http://txstyle.org/article/36/php-textile
[twig]: http://twig.sensiolabs.org
[yaml]: http://www.yaml.org
[geshi]: http://qbnz.com/highlighter/
[pimple]: http://pimple.sensiolabs.org
[composer]: http://getcomposer.org
[packagist]: https://packagist.org
[symfony]: http://symfony.com/doc/current/components/
[phpunit]: http://phpunit.de
[imagine]: https://github.com/avalanche123/Imagine
