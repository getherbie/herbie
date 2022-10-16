---
title: Konfiguration
layout: doc
---

# Konfiguration

Herbie CMS nutzt eine Konfigurationsdatei, um Einstellungen festzuhalten.
Diese Default-Konfigurationen sind in der Datei [config/defaults.php](https://github.com/getherbie/herbie/blob/2.x/config/defaults.php) festgelegt.

In der Konfigurationsdatei stehen die folgenden Variablen als PHP-Konstanten und einfache Strings zur Verfügung:

    APP_PATH
    SITE_PATH
    WEB_PATH
    WEB_URL

Diese Konstanten-Strings werden zur Laufzeit geparst.


## Einstellungen übersteuern

Alle Default-Einstellungen können in einer eigenen Konfigurationsdatei übersteuert werden.
Diese Datei muss unter `site/config/main.php` abgelegt sein.

Findet Herbie CMS eine Datei mit gültigen Werten, werden die Default-Einstellungen mit diesen überschrieben.


## Beispiel

Für ein Projekt sollen schön lesbare URLs und das Twig-Debugging aktiviert werden.

Dazu wird eine Datei `site/config/main.php` mit folgendem Inhalt erstellt:

~~~php
<?php

return [
    'niceUrls' => true,
    'components' => [
        'twigRenderer' => [
            'debug' => true
        ]
    ]
];
~~~

Damit wurden die gewünschten Einstellungen übersteuert.

## Punkt-Notation

Auf alle Konfigurationswerte kann über eine Funktion mit Unterstützung für Punktnotation zugegriffen werden.

Die folgenden Funktionsaufrufe:

{% verbatim %}
    {{ site.config.getAsString('charset') }}
    {{ site.config.getAsString('paths.web') }}
    {{ site.config.getAsBool('components.virtualCorePlugin.enableTwigInLayoutFilter') }}
{% endverbatim %}

Geben die folgenden Werte zurück:

    Charset = {{ site.config.getAsString('charset') }}
    Web-Path = {{ site.config.getAsString('paths.web') }}
    Enabled = {{ site.config.getAsBool('components.virtualCorePlugin.enableTwigInLayoutFilter') }}
