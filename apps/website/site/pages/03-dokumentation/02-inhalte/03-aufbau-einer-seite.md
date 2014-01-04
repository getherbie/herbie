---
title: Aufbau einer Seite
layout: documentation.html
---

# Aufbau einer Seite

In den vorherigen Kapiteln hast du gelernt, dass eine Seite einer Textdatei im
Seitenverzeichnis entspricht. Eine Textdatei könnte im einfachsten Fall ungefähr
so aussehen:

    ---
    title: A basic page
    ---

    A basic page with some text.

Herbie erzeugt einen Seitentitel und fügt den Text unterhalb des Front-Matter Blocks dem Standard Inhaltscontainer hinzu.


## Benannte Platzhalter

Die meisten Websites sind leider nicht so einfach gehalten. In der Regel
basieren sie auf mehrspaltigen Layouts, die man unabhängig befüllen möchte.
Dies kannst du mit Herbie erreichen, indem du benannte Platzhalter nutzt.
Ein Platzhalter wird mit drei Minuzzeichen, gefolgt von einer Zahl und weiteren
drei Minuszeichen definiert, also zum Beispiel `--- 2 ---`. Der nachfolgende
Text wird dann diesem Platzhalter zugeordnet.

    ---
    title: A page with placeholders
    ---

    A extended page with some text and placeholders.

    --- 1 ---

    Use this text in content container 1.

    --- 2 ---

    Use this text in content container 2.


Auf diese Art sind komplexe Layouts auch mit den einfachen Textdateien von
Herbie zu bewältigen.


## Platzhalter im Layout ausgeben

Inhalte eines Platzhalters werden in den Layoutdateien über die Twig-Funktion
`{{ text.raw('{{ content() }}') }}` ausgegeben. Die Content-Funktion erwartet
als einzigen Parameter die Platzhalter-ID.

Beispiele dazu findest du unter dem Kapitel Templates.


<p class="pagination">{{ link('dokumentation/inhalte/variablen', 'Variablen<i class="fa fa-arrow-right"></i>', {class:'pure-button'}) }}</p>