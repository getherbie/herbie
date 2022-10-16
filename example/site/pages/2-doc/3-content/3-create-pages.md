---
title: Seiten erstellen
layout: doc
---

# Seiten erstellen

## Das Seiten-Verzeichnis

Alle Seiten einer Herbie CMS Website sind im Verzeichnis `site/pages` als einfache Textdateien abgelegt. 
Diese Textdateien können reine Text-, Markdown-, Textile- oder HTML-Dateien sein.

Damit Herbie CMS diese Dateien erkennt und in eine HTML-Datei konvertieren kann, müssen diese im Kopf der Datei einen Block mit Seiteneigenschaften enthalten.


## Erlaubte Dateien

Herbie CMS unterstützt zur Zeit die folgenden Dateitypen:

    DATEIENDUNG     FORMATIERER
    .markdown       Markdown
    .md             Markdown
    .textile        Textile
    .htm            HTML (keine Konvertierung)
    .html           HTML (keine Konvertierung)
    .txt            Text (keine Konvertierung)
    .rss            Text (keine Konvertierung)
    .xml            Text (keine Konvertierung)

Beim Parsen des Inhalts wird der Formatierer eingesetzt, der der Dateiendung entspricht.
Der Inhalt einer Datei mit der Endung .md wird somit vom Markdown-Parser umgewandelt. 
Derjenige einer Datei mit der Endung .textile von einem Textile-Parser.


## Eine Seite erstellen

Um eine neue Seite zu erstellen, fügt man im Verzeichnis `site/pages` eine neue Datei mit einer der erlaubten Endungen hinzu. 
Dabei muss man die folgenden Regeln beachten:

- nur Kleinbuchstaben, Zahlen, Unter- und Bindestriche
- keine Umlaute, Sonder- und Steuerzeichen
- keine Leerschläge

Wie man die Datei benennt, hat Einfluss auf die Webadresse und wie die Seite im Browser aufgerufen wird. 
Wenn man obige Regeln befolgt, bekommt man schön lesbare und funktionierende Links zu den Unterseiten der Website.


## Homepage

Als einzige Datei im Seiten-Verzeichnis erwartet Herbie CMS eine Index-Datei mit einer der obigen Endungen. 
Diese Datei übernimmt die Funktion der Homepage oder Startseite und wird angezeigt, wenn man *http://www.example.com* im Browser aufruft. 
Fehlt die Index-Datei, wird eine 404-Fehlerseite angezeigt.


## Benannte Textdateien

Der einfachste Weg, Seiten hinzuzufügen, ist das Hinzufügen einer Textdatei mit einem passenden Namen im Seiten-Verzeichnis. 
Für eine Website mit einer Homepage, einer "Über uns" Seite und einer Kontaktseite würde das Seitenverzeichnis und ihre entsprechenden URLs wie folgt aussehen:

    site/pages
    |-- index.md        # http://example.com
    |-- about.md        # http://example.com/about
    └── contact.md      # http://example.com/contact


## Benannte Ordner mit Index-Dateien

Man kann dies so machen, und es ist überhaupt nichts falsch dabei. 
Oft möchte man aber weitere Seiten hinzufügen oder bestehende Seiten in einem Themenbereich gruppieren. 
Kommen zur obigen Website beispielsweise eine Team-, eine Vision- und eine Anfahrt-Seite hinzu, könnte das Seitenverzeichnis so aussehen:

    site/pages
    ├── index.md        # http://example.com
    ├── about/
    |   ├── index.md    # http://example.com/about
    |   ├── team.md     # http://example.com/about/team
    |   └── vision.md   # http://example.com/about/vision
    ├── contact/
    |   ├── index.md    # http://example.com/contact
    |   └── route.md    # http://example.com/contact/route
    └── index.md        # http://example.com


Welcher Weg der bessere ist, hängt stark von der Art der Website ab. 
Für kleine Websites reichen benannte Textdateien ohne weitere Unterordner. 
Für umfangreiche Websites wird man um weitere Unterordner und Textdateien nicht herumkommen.


## Sichtbarkeit und Sortierung

Indem man Dateien eine Zahl voranstellst, kannst man die Sortierung und Sichtbarkeit in Menüs steuern. 
Das sieht dann zum Beispiel so aus:

    site/pages
    |-- 1-index.md
    |-- 2-ueber-uns.md
    |-- 3-kontakt.md
    |-- sitemap.md
    └── impressum.md

Die Seiten *index*, *ueber-uns* und *kontakt* sind in Menüs sichtbar, und die Sortierung ist definiert. 
Die Seiten *sitemap* und *impressum* sind in Menüs nicht sichtbar und die Sortierung somit nicht relevant.

**Hinweis:** Bei Ordnern hat die vorangestellte Zahl nur Einfluss auf die Sortierung, nicht aber auf die Sichtbarkeit.


## Seite oder Ordner deaktivieren

Manchmal möchte man eine Seite oder einen ganzen Ordner deaktivieren. 
Dies erreicht man, indem man dem Namen der Seite oder des Ordners einen Unterstrich voranstellst. 
Solche Seiten und Ordner werden beim Scannen des Dateisystems nicht berücksichtigt.

    site/pages
    ├── index.md
    ├── _about/         # Der Ordner inkl. Unterseiten ist deaktiviert
    |   ├── index.md
    |   ├── team.md
    |   └── vision.md
    └── contact/
        ├── index.md
        └── _route.md   # Die Seite ist deaktiviert

Dieselbe Regel können natürlich auch bei Blogposts angewendet werden.
