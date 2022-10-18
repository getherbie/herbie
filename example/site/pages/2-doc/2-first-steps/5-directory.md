---
title: Verzeichnisstruktur
layout: doc
---

# Verzeichnisstruktur

Eine Herbie CMS Website ist in der Regel wie folgt aufgebaut:

    project
    ├── site
    ├── vendor
    └── web
        ├── assets
        ├── cache
        ├── media
        ├── .htaccess
        └── index.php


Wofür diese Dateien und Verzeichnisse stehen, ist in der folgenden Tabelle ersichtlich:

<table class="pure-table pure-table-horizontal" width="100%">
    <thead>
        <tr>
            <th width="35%">Datei/Verzeichnis</th>
            <th width="65%">Beschreibung</th>
        </tr>
    </thead>
    <tbody>
    {% for data in site.data.site_dir_basic %}
        <tr>
            <td><code>{{ data.name }}</code></td>
            <td>{{ data.desc }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>


## site-Verzeichnis

Normalerweise arbeitet man ausschliessliche im `site`-Verzeichnis des Webprojektes.
Dieses ist normalerweise wie folgt aufgebaut:

    site
    ├── assets
    ├── config
    |   └── main.yml
    ├── data
    |   └── persons.yml
    ├── extend
    |   ├── events
    |   ├── filters
    |   ├── plugins
    |   ├── twig_filters
    |   ├── twig_functions
    |   ├── twig_globals
    |   └── twig_tests
    ├── pages
    |   ├── index.md
    |   ├── company
    |   |   ├── index.md
    |   |   ├── about-us.md
    |   |   ├── our-vision.md
    |   |   └── team.md
    |   ├── services.md
    |   └── contact.md
    ├── runtime
    |   ├── cache
    |   |   ├── data
    |   |   ├── page
    |   |   └── twig
    |   └── log
    └── themes
        └─ default
            ├── error.html
            └── default.html


In der folgenden Tabelle ist ersichtlich, wofür jede dieser Dateien und Verzeichnisse stehen:

<table class="pure-table pure-table-horizontal" width="100%">
    <thead>
        <tr>
            <th width="35%">Datei/Verzeichnis</th>
            <th width="65%">Beschreibung</th>
        </tr>
    </thead>
    <tbody>
    {% for data in site.data.site_dir_extended %}
        <tr>
            <td><code>{{ data.name }}</code></td>
            <td>{{ data.desc }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>


## .htaccess-Datei

Falls in der Konfiguration die Option `niceUrls` aktiviert ist, muss im `web`-Verzeichnis eine .htaccess-Datei mit den entsprechenden Anweisungen vorhanden sein.

    RewriteEngine on
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . index.php

Damit werden alle Seitenaufrufe an die zentrale Index-Datei weitergereicht.
Dies ist zum Beispiel für die Suchmaschinen-Optimierung wichtig, aber auch für die Besucher der Website.

Hinweis: Die obige Konfiguration ist für den Apache Webserver ausgelegt.
