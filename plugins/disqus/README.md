Herbie Disqus Plugin
====================

`Disqus` ist ein [Herbie](http://github.com/getherbie/herbie) Plugin, mit dem du den gleichnamigen Service 
[Disqus](http://www.disqus.com) in deine Website einbettest.

## Installation

Das Plugin installierst du am einfachsten via Composer.

	$ composer require getherbie/plugin-disqus

Danach aktivierst du das Plugin in der Konfigurationsdatei.

    plugins:
        disqus:


Anwendung
---------

Nach der Installation steht dir die Twig-Funktion `disqus` zur Verfügung. Diese rufst du wie folgt auf:

    {{ disqus("getherbie") }}


Parameter
---------

Name        | Beschreibung                          | Typ       | Default
:---------- | :------------------------------------ | :-------- | :------
shortname   | Der Disqus Shortname                  | string    |  
