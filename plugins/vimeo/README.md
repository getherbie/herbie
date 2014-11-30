Herbie Vimeo Plugin
===================

`Vimeo` ist ein [Herbie](http://github.com/getherbie/herbie) Plugin, mit dem du Videos von [Vimeo](https://vimeo.com) 
in deine Website einbettest.

Installation
------------

Das Plugin installierst du via Composer.

	$ composer require getherbie/plugin-vimeo

Danach aktivierst du das Plugin in der Konfigurationsdatei.

    plugins:
        vimeo:
        
        
Anwendung
---------

Nach der Installation steht dir die Twig-Funktion `vimeo` zur Verfügung. Diese rufst du wie folgt auf:

    {{ vimeo("30459532", 480, 320) }}

Alternativ kannst du die Funktion auch mit benannten Argumenten aufrufen.

    {{ vimeo(id="30459532", width=480, height=320, responsive=1) }}


Parameter
---------

Name        | Beschreibung                          | Typ       | Default
:---------- | :------------------------------------ | :-------- | :------
id          | Die ID des Vimeo-Videos               | string    |  
width       | Die Breite des Videos in Pixel        | int       | 480
height      | Die Höhe des Videos in Pixel          | int       | 320
responsive  | Definiert ob das Video responsiv ist  | bool      | true

