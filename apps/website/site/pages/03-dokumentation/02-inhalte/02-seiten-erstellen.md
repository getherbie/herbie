---
title: Seiten erstellen
layout: documentation.html
---

# Seiten erstellen

{{ text.lipsum(40) }}.

## Das Seiten-Verzeichnis

Alle Seiten einer Herbie-Website sind im Verzeichnis `site/pages` als reine
Textdateien abgelegt. Diese Textdateien können entweder Markdown-, Textile- oder
auch HTML-formatierte Dateien sein. Sie müssen einfach den YAML Front-Matter
Textblock enthalten, damit Herbie sie in eine HTML-Datei konvertieren kann.


## Eine Seite erstellen

Um eine neue Seite zu erstellen, musst du im Verzeichnis `site/pages` eine neue
Datei hinzufügen. Wie du die Datei benennst, hat Einfluss auf die URL dieser
Seite.

## Erlaubte Dateieindungen

Herbie unterstützt zur Zeit die folgenden Dateiendungen. Beim Konvertieren des
Inhalts wird der Parser eingesetzt, der der Dateiendung entspricht:

    ENDUNG          PARSER
    .txt            Text (keine Konvertierung)
    .markdown       Markdown
    .md             Markdown
    .textile        Textile
    .htm            HTML (keine Konvertierung)
    .html           HTML (keine Konvertierung)


## Homepage

Als einzige Datei im Seiten-Verzeichnis erwartet Herbie eine Index-Datei mit
einer der obigen Endungen. Diese Datei übernimmt die Funktion der Homepage oder
Startseite und wird angezeigt, wenn man *http://www.example.com* im Browser
aufruft. Fehlt die Index-Datei, wird eine 404-Fehlerseite angezeigt.


## Benannte Textdateien

Der einfachste Weg, Seiten hinzuzufügen, ist das Hinzfügen einer Textdatei mit
einem passenden Namen im Seiten-Verzeichnis. Für eine Website mit einer
Homepage, einer "Über uns" Seite und einer Kontaktseite würde das
Seitenverzeichnis und ihre entsprechenden URLs wie folgt aussehen:

    site/pages
    |-- index.md        # http://example.com
    |-- about.md        # http://example.com/about
    └── contact.md      # http://example.com/contact


## Benannte Ordner mit Index-Dateien

Man kann dies so machen, und es ist überhaupt nichts falsch dabei. Oft möchte
man aber weitere Seiten hinzufügen oder bestehende Seiten in einem Themenbereich
gruppieren. Kommen zum obigen Projekt beispielsweise eine Team-, eine Vision und
eine Anfahrt-Seite hinzu, könnte das Seitenverzeichnis so aussehen:

    site/pages
    ├── index.md        # http://example.com
    ├── about/
    |   ├── index.md    # http://example.com/about
    |   ├── team.md     # http://example.com/about/team
    |   └── vision.md   # http://example.com/about/vision
    ├── contact/
    |   ├── index.md    # http://example.com/about
    |   └── route.md    # http://example.com/contact/route
    └── index.md        # http://example.com


Beide Wege funktionieren, die Entscheidung liegt bei Dir.


<p class="pagination">{{ link('dokumentation/inhalte/aufbau-einer-seite', 'Aufbau einer Seite<i class="fa fa-arrow-right"></i>', {class:'pure-button'}) }}</p>