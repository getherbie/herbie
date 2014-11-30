# Herbie Google Maps Plugin

`Google Maps` ist ein [Herbie](http://github.com/getherbie/herbie) Plugin, mit dem du 
[Google Maps](http://maps.google.com)-Karten in deine Website einbettest.

## Installation

Das Plugin installierst du via Composer.

	$ composer require getherbie/plugin-googlemaps

Danach aktivierst du das Plugin in der Konfigurationsdatei.

    plugins:
        googlemaps:


Anwendung
---------

Nach der Installation steht dir die Twig-Funktion `googlemaps` zur Verfügung. Diese rufst du wie folgt auf:

    {{ googlemaps("gmap", 600, 450, "roadmap", "gmap", 15, "Baslerstrasse 8048 Zürich") }}

Alternativ kannst du die Funktion auch mit benannten Argumenten aufrufen.

    {{ googlemaps(address="Baslerstrasse 8048 Zürich", type="roadmap") }}


Parameter
---------

Name        | Beschreibung                          | Typ       | Default
:---------- | :------------------------------------ | :-------- | :------
id | Das `id` HTML-Attribut | string | gmap  
width | Die Breite des Videos in Pixel | int | 600
height | Die Höhe des Videos in Pixel | int | 450
type | Der Kartentyp | string | roadmap
class | Das `class` HTML-Attribut | string | gmap
zoom | Der Zoomfaktor | int | 15
address | Die Adresse, die geokodiert werden soll | string | 

