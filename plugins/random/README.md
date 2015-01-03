Random Plugin
=============

`Random` ist ein [Herbie](http://github.com/getherbie/herbie) Plugin, mit dem du auf einer beliebigen Seite 
zufallsgesteurt den Inhalt einer anderen Seite anzeigst.

## Installation

Das Plugin installierst du via Composer.

	$ composer require getherbie/plugin-random

Danach aktivierst du das Plugin in der Konfigurationsdatei.

    plugins:
        enable:
            - random
        
Die Seite, auf welcher das Random-Plugin aktiv sein soll, muss wie folgt aktiviert werden. Optional kannst du eine oder
mehrere Seiten ausschliessen, indem du die Route(n) angibst.
        
    ---
    title: Zufall
    noCache: 1
    random:
        excludes: [blog,kontakt]
    ---
