Herbie YouTube Plugin
=====================

`YouTube` ist ein [Herbie](http://github.com/getherbie/herbie) Plugin, mit dem du Videos von 
[YouTube](http://www.youtube.com) in deine Website einbettest.


Installation
-------------

Das Plugin installierst du am einfachsten via Composer.

	$ composer require getherbie/plugin-youtube

Danach aktivierst du das Plugin in der Konfigurationsdatei.

    plugins:
        youtube:


Anwendung
---------

Nach der Installation steht dir die Twig-Funktion `youtube` zur Verfügung. Diese rufst du wie folgt auf:

    {{ youtube("_0TfPpjDkWU", 480, 320) }}

Alternativ kannst du die Funktion auch mit benannten Argumenten aufrufen.

    {{ youtube(id="", width="480", height="320", responsive="1") }}


Parameter
---------

Name        | Beschreibung                          | Typ       | Default
:---------- | :------------------------------------ | :-------- | :------
id          | Die ID des YouTube-Videos             | string    |  
width       | Die Breite des Videos in Pixel        | int       | 480
height      | Die Höhe des Videos in Pixel          | int       | 320
responsive  | Definiert ob das Video responsiv ist  | bool      | true

