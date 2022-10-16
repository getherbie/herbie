---
title: Seiteneigenschaften
layout: doc
---

# Seiteneigenschaften

Jede Datei, die einen Block mit Seiteneigenschaften enthält, wird von Herbie CMS als gültige Seite betrachtet. 
Der Block mit den Seiteneigenschaften muss am Anfang der Datei stehen.
Zwischen zwei Linien aus drei Minuszeichen muss gültiges YAML enthalten sein. 
Das tönt etwas kompliziert, ist aber ganz einfach. 
Hier ist ein Beispiel:

    ---
    title: Mit der eigenen Website durchstarten
    layout: default.html
    ---

Als Seiteneigenschaften nutzt man vordefinierte Variablen (siehe Referenz unten) oder eigene Variablen.
Diese Variablen sind in allen Layout- und Inhaltsdateien als Seiten-Variable verfügbar.
Hier ist ein Beispiel:

{% verbatim %}
    {{ page.title }}
    {{ page.layout }}
{% endverbatim %}


## Vordefinierte Variablen

Es gibt einige vordefinierte (=reservierte) Variablen, die vom System verwendet werden.
Diese kannt man im Seiteneigenschaften-Block einer Seite mit einem Wert belegen.

{{ snippet(path="@site/snippets/variables.twig", type="vars_page")|raw }}


## Eigene Variablen

Jede eigene Variable im Seiteneigenschaften-Block, die nicht vordefiniert ist, wird von Herbie CMS automatisch erkannt und in den Layoutdateien und in den Seiteninhalten zur Verfügung gestellt. 
Wenn man zum Beispiel eine Variable `class` deklariert, kannst man diese in der Layoutdatei abrufen und zum Setzen einer CSS-Klasse nutzen.

In den Seiteneigenschaften deklariert man den Wert der Variablen:

    ---
    title: Willkommen auf meiner Homepage!
    class: home
    ---

In den Seiteninhalten selber kann man auf diese Variablen wie folgt zugreifen:

    [[page.title]]
    [[page.layout]]

Und im Layout gibt man die Variablen aus:

{% verbatim %}
    <!DOCTYPE HTML>
    <html>
    <head>
        <title>{{ page.title }}</title>
    </head>
    <body class="{{ page.class }}">
        ...
    </body>
    </html>
{% endverbatim %}

Damit können Seitenvariablen eingesetzt und mit weiteren eigenen Variablen angereichert werden.
