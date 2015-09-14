Cheküll
======

Cheküll ist ein einfaches Flat-File CMS- und Blogsystem, das auf simplen Textdateien basiert.

## Was ist Cheküll?

Mit Cheküll erstellst du mit einfachen Markdown- und Textiledateien in kurzer Zeit und mit wenig Aufwand eine voll
funktionsfähige Website oder einen Blog.

Cheküll baut auf bewährten Bibliotheken und Komponenten auf.

* [Markdown][markdown] und [Textile][textile] zur Formatierung von Inhalten
* [Twig][twig] Template Engine zur Erstellung von Layouts
* [Yaml][yaml] zum Konfigurieren der Website und Verwalten von Datenstrukturen
* [Pimple][pimple] als Dependency Injection Container
* [Composer][composer] und [Packagist][packagist] für das Dependency Management
* die HttpFoundation-, EventDispatcher- und Yaml-Komponenten der [Symfony Components][symfony]
* [Imagine][imagine] zur Bildmanipulation und -bearbeitung
* [GeSHi][geshi] als leistungsfähgier Code Syntaxhighlighter

## Installation

Am einfachsten installierst du Cheküll via Composer. Führe dazu im Terminal die folgende Anweisung aus:

    $ composer create-project getcheckuell/start-website:dev-master myproject

Composer erstellt im Verzeichnis `myproject` eine Website und installiert alle abhängigen Bibliotheken.

## Website

Weitere Informationen findest du unter [www.getcheckuell.org](http://www.getcheckuell.org).


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
