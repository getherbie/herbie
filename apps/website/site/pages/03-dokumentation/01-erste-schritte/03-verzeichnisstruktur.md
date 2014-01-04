---
title: Verzeichnisstruktur
layout: documentation.html
---

# Verzeichnisstruktur

Eine Herbie-Website ist in der Regel wie folgt aufgebaut:

    project
    ├── herbie
    └── web
        ├── assets
        ├── media
        ├── site
        └── index.php


Eine Übersicht, was jedes dieser Dateien/Verzeichnisse macht:


<table class="pure-table pure-table-horizontal" width="100%">
    <thead>
        <tr>
            <th width="35%">Datei/Verzeichnis</th>
            <th width="65%">Beschreibung</th>
        </tr>
    </thead>
    <tr>
        <td><code>project</code></td>
        <td>Dein Projektverzeichnis auf dem Webserver.</td>
    </tr>
    <tr>
        <td><code>herbie</code></td>
        <td>Die eigentliche Applikation. In diesem Verzeichnis musst du nichts anpassen.
        Dieses Verzeichnis ist nicht über das Web zugänglich.</td>
    </tr>
    <tr>
        <td><code>web</code></td>
        <td>Das öffentliche Verzeichnis deines Webservers. Du hast darauf über den
        Webbrowser Zugriff.</td>
    </tr>
    <tr>
        <td><code>assets</code></td>
        <td>Ablage für JavaScript-, CSS- und Bilddateien. Diese sind für das Layout der
        Website nötig.</td>
    </tr>
    <tr>
        <td><code>media</code></td>
        <td>Hier sind Dateien abgelegt, die du über deine Inhalte verlinkt sind (Bilder,
        Videos, MP3, etc.).</td>
    </tr>
    <tr>
        <td><code>site</code></td>
        <td>Der eigentliche Inhalt der Website (siehe unten).</td>
    </tr>
    <tr>
        <td><code>index.php</code></td>
        <td>Die Bootstrap-Datei und Teil von Herbie. Über diese Datei laufen alle Anfragen
        an den Webserver.</td>
    </tr>
</table>


Normalerweise arbeitest du nur im `site`-Verzeichnis deines Webprojektes. Dieses ist in
der Regel wie folgt aufgebaut:

    site
    ├── config.yml
    ├── cache
    ├── data
    |   └── persons.yml
    ├── layouts
    |   ├── head.html
    |   ├── footer.html
    |   ├── error.html
    |   └── default.html
    ├── pages
    |   ├── index.md
    |   ├── company
    |   |   ├── index.md
    |   |   ├── about-us.md
    |   |   ├── our-vision.md
    |   |   └── team.md
    |   ├── services.md
    |   └── contact.md
    ├── posts
    |   ├── 2013-10-29-my-third-blog-post.md
    |   ├── 2007-10-29-my-second-blog-post.md
    |   └── 2007-10-29-my-new-blog.md
    └── index.md


Und wieder eine Übersicht, was jedes dieser Dateien/Verzeichnisse macht:

<table class="pure-table pure-table-horizontal" width="100%">
    <thead>
        <tr>
            <th width="35%">Datei/Verzeichnis</th>
            <th width="65%">Beschreibung</th>
        </tr>
    </thead>
    <tr>
        <td><code>config.yml</code></td>
        <td>Die Konfigurationsdatei im YAML-Format.</td>
    </tr>
    <tr>
        <td><code>cache</code></td>
        <td>Das Cache-Verzeichnis von Herbie. Darin werden z.B. Twig Cache-Dateien
        gespeichert.</td>
    </tr>
    <tr>
        <td><code>data</code></td>
        <td>Das Daten-Verzeichnis, im dem verschiedene Daten-Dateien im YAML-Format
        gespeichert werden können.</td>
    </tr>
    <tr>
        <td><code>layouts</code></td>
        <td>Das Layout-Verzeichnis der Website. Hier sind HTML-Dateien abgelegt, die für
        das Aussehen der Website zuständig sind.</td>
    </tr>
    <tr>
        <td><code>pages</code></td>
        <td>Die eigentlichen Inhalte der Website. Diese sind als Textdateien (Markdown,
        Textile) abgespeichert.</td>
    </tr>
    <tr>
        <td><code>posts</code></td>
        <td>Das Verzeichnis mit den Blog-Posts.</td>
    </tr>
    <tr>
        <td><code>index.md</code></td>
        <td>Die Startseite deines Webprojektes.</td>
    </tr>
</table>

<p class="pagination">{{ link('dokumentation/erste-schritte/konfiguration', 'Konfiguration<i class="fa fa-arrow-right"></i>', {class:'pure-button'}) }}</p>