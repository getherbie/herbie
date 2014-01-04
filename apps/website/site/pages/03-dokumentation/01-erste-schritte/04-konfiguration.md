---
title: Konfiguration
layout: documentation.html
---

# Konfiguration

Du kannst viele Einstellungen von Herbie deinen Bedürfnissen anpassen. Solange
die Default-Einstellungen in `site/config.yml` nicht explizit übersteuert
werden, läuft Herbie mit den folgenden Einstellungen.

    app:
      path: APP_PATH

    site:
      path: SITE_PATH

    data:
      path: SITE_PATH/data
      extensions: [yml, yaml]

    layouts:
      path: SITE_PATH/layouts

    pages:
      path: SITE_PATH/pages

    posts:
      path: SITE_PATH/posts

    nice_urls: false

    twig:
      debug: true
      cache: SITE_PATH/cache/twig

    cache:
      page:
        enable: false
        dir: SITE_PATH/cache/page
        expire: 18000
      data:
        enable: false
        dir: SITE_PATH/cache/data
        expire: 18000


Die folgenden Konstanten stehen dir zur Verfügung. Diese kannst du in der
YAML-Datei nutzen und macht die Konfiguration etwas übersichtlicher. Die
Konstanten werden zur Laufzeit in echte Pfade umgewandelt.

<table class="pure-table pure-table-horizontal" width="100%">
    <thead>
        <tr>
            <th width="25%">Variable</th>
            <th width="75%">Beschreibung</th>
        </tr>
    </thead>
    <tr>
        <td><code>APP_PATH</code></td>
        <td>Pfad zum Applikations-Verzeichnis von Herbie.</td>
    </tr>
    <tr>
        <td><code>WEB_PATH</code></td>
        <td>Pfad zum Web-Verzeichnis deines Projekts.</td>
    </tr>
    <tr>
        <td><code>SITE_PATH</code></td>
        <td>Pfad zum Site-Verzeichnis deines Projekts.</td>
    </tr>
</table>




<p class="pagination">{{ link('dokumentation/inhalte/index', 'Front-Matter<i class="fa fa-arrow-right"></i>', {class:'pure-button'}) }}</p>