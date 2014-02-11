---
title: Übersicht
date: 2013-12-27
---

Mit Herbie erstellst du mit einfachen Textdateien (Flat-Files) in kurzer Zeit
und mit wenig Aufwand eine voll funktionsfähgie Website oder einen Blog.


--- boxes ---

<box>
## Einfach
Keine Datenbank, keine Moderation von Kommentaren, keine mühselige Installation
von Updates — nur dein eigener Inhalt.
</box>

<box>
## Statisch
Herbie verwandelt einfache Textdateien im Markdown- oder Textile-Format in eine
Website oder einen Blog.
</box>

<box>
## Installation
Mit einer Zeile PHP-Code installierst du dein neues Webprojekt. Natürlich ist
Herbie auch als Download verfügbar.
</box>

<box>
## Erweiterbar
Herbie ist einfach erweiterbar. Mit ein paar Zeilen PHP-Code programmierst du
deine eigenen Twig-Funktionen.
</box>

--- footer ---

## Technologie

Herbie baut auf bewährten Bibliotheken und Komponenten auf.

- [Markdown][1] und [Textile][2] für die Formatierung von Inhalten
- [Twig][3] (eine von Jinja/Django inspirierte Template Engine) für die
  Erstellung von Layouts
- [Yaml][4] zum Konfigurieren der Website und Hinzufügen von Datenstrukturen
- [GeSHi][5] als leistungsfähgier Code Syntaxhighlighter
- [Pimple][6] als Dependency Injection Container
- [Composer][7] und [Packagist][8] für das Dependency Management
- die HttpFoundation- und Yaml-Komponente der [Symfony Components][9]
- [PHPUnit][10] zum Testen des Ganzen

[1]: http://daringfireball.net/projects/markdown/
[2]: http://txstyle.org/article/36/php-textile
[3]: http://twig.sensiolabs.org
[4]: http://www.yaml.org
[5]: http://qbnz.com/highlighter/
[6]: http://pimple.sensiolabs.org
[7]: http://getcomposer.org
[8]: https://packagist.org
[9]: http://symfony.com/doc/current/components/
[10]: http://phpunit.de