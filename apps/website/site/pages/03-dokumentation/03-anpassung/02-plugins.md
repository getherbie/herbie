---
title: Plugins
layout: documentation.html
---

# Plugins

Herbie kann auf verschiedene Art und Weise erweitert werden.


## Twig Funktionen

Wie man eine Twig-Funktion hinzufügt, lässt sich am Einfachsten anhand eines
Beispiels erkläaren. Nehmen wir an, du möchtest eine Funktion *hello*, der du
einen Parameter *$name* übergeben kannst, und die dich freundlich begrüsst.
Dazu erstellst du im Verzeichnis `site/plugins/twig/functions` eine gleichnamige
PHP-Datei und fügst folgenden Code hinzu:

    <?php
    return new Twig_SimpleFunction('hello', function ($name) {
        return "Hallo {$name}!";
    });

Im Layout rufst du dann die neue Funktion wie folgt auf:

    {{ text.raw("{{ hello('Herbie') }}")|raw }}

Du solltest folgende Ausgabe sehen:

    Hallo Herbie!


## Twig Filter

Auch hier erklärt ein praktisches Beispiel am Einfachsten, wie ein Twig-
Filter erstellt werden kann. Nehmen wir an, du möchtest einen Filter *reverse*,
der einen beliebigen String umgekehrt ausgeben soll. Dazu erstellst du im
Verzeichnis `site/plugins/twig/filters` eine gleichnamige PHP-Datei und fügst
den folgenden Code hinzu.

    <?php
    return new Twig_SimpleFilter('reverse', function ($string) {
        return strrev($string);
    });

Im Layout rufst du den neuen Filter wie folgt auf:

    {{ text.raw("{{ 'looc tsi eibreH'|reverse }}")|raw }}


Du solltest folgende Ausgabe sehen:

    Herbie ist cool

## Twig Tests

Tests funktionieren gleich wie Funktionen, mit dem Unterschied, dass der
Rückgabewert ein boolscher Wert ist. Tests kannst du also für Konditionen in
Layouts nutzen.
Nehmen wir an, du möchtest einen Test *odd*, der eine Zahl darauf testet, ob
diese ungerade ist. Dazu erstellst du im Verzeichnis `site/plugins/twig/tests`
eine gleichnamige PHP-Datei und fügst den folgenden Code hinzu.

    <?php
    return new Twig_SimpleTest('odd', function ($value) {
        return ($value % 2) != 0;
    });

Im Layout setzst du den neuen Test wie folgt ein:

    {{ text.raw('{% if 3 is odd() %}
        Die Zahl 3 ist ungerade.
    {% else %}
        Die Zahl 3 ist gerade.
    {% endif %}') }}

Du solltest folgende Ausgabe sehen:

    Die Zahl 3 ist ungerade.
