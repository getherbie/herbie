# Herbie Imagine Plugin

`Imagine` ist ein [Herbie](http://github.com/getherbie/herbie) Plugin, das die gleichnamige OOP-Library zur Bildbearbeitung [Imagine](https://imagine.readthedocs.org) in deine Website einbindet.

## Installation

Das Plugin installierst du via Composer.

	$ composer require getherbie/plugin-imagine

Danach aktivierst du das Plugin in der Konfigurationsdatei.

    plugins:
        enable:
            - imagine

## Konfiguration

In der Konfigurationsdatei definierst du die gewünschten Filter.

    plugins:
        config:
            imagine:
                filter_sets:
                    resize:
                        filters:
                            thumbnail:
                                size: [280, 280]
                                mode: inset
                    crop:
                        filters:
                            crop:
                                start: [0, 0]
                                size: [560, 560]

Mit obigen Einstellungen stehen dir zwei Filter *resize* und *crop* zur
Verfügung, die du in Seiten und Layouts als Twig-Funktion oder Twig-Filter
einsetzen kann.

### Twig-Funktion

    {{ imagine('mein-bild.jpg', 'resize') }}
    {{ imagine('mein-bild.jpg', 'crop') }}

### Twig-Filter

    <img src="{{ 'mein-bild.jpg'|imagine('resize') }}" alt="" />
    <img src="{{ 'mein-bild.jpg'|imagine('crop') }}" alt="" />
