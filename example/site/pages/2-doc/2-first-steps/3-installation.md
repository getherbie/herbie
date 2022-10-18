---
title: Installation
layout: doc
---

# Installation


## Systemanforderungen

Es gibt nur wenige Anforderungen an das Host-System, die erfüllt sein müssen. 
Diese sind:

- Betriebssystem: Windows, Linux oder Mac
- PHP: >=7.4
- Composer: >=2.x


## Composer-Unterstützung

Herbie CMS wird am einfachsten via Composer installiert. 
Dazu führt man im Terminal den folgenden Befehl aus:

    composer create-project getherbie/start-website mywebsite

Composer erstellt im Verzeichnis *mywebsite* eine Website-Vorlage und installiert alle abhängigen Pakete.

Tipp: Um die Installation zu beschleunigen und das Vendor-Verzeichnis so schlank wie möglich zu halten, kann man die Option `--prefer-dist` verwenden.

    composer create-project --prefer-dist getherbie/start-website mywebsite

Eventuell muss der Eigentümer des erstellten Verzeichnisses rekursiv geändert werden.
Dies ist abhängig vom Host-System und dessen Einstellungen.

    chown -R new-owner mywebsite

Danach wechselt man in das `web`-Verzeichnis des erstellten Projektes und startet den internen PHP-Webserver.

    cd mywebsite/web
    php -S localhost:8888 index.php

Die Website kann nun im Browser unter `http://localhost:8888` geöffent werden.
