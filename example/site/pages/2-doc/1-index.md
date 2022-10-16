---
title: Documentation
layout: doc
---

# Dokumentation

In dieser Dokumentation werden die wichtigsten Themen vermittelt, um eine Website oder einen Blog mit Herbie CMS zu erstellen.

## Was ist Herbie CMS genau?

Herbie CMS ist ein dateibasiertes CMS-System, das ohne Datenbank auskommt.
Die Inhalte der Website werden vom Dateisystem aus einfachen Textdateien geladen.
Das Programm durchläuft dabei ein definiertes Verzeichnis mit Textdateien, wandelt diese Dateien entsprechend ihrer Dateiendung um
und gibt sie - eingebettet in ein HTML-Layout - als vollständige Website aus.

Herbie CMS ist beinflusst von flat-file CMS-Systemen aus der Ruby- , Go- und PHP-Welt. Darunter sind
Namen wie [Grav][4], [Hugo][3], [Jekyll][1], [Stacey][5] oder [Statamic][2], um nur einige zu nennen. 
Das Projekt entstand aus dem Bedürfnis, ein System zur Verfügung zu haben, mit dem sich einfach und schnell Websites umsetzen lassen. 
Bei der Entwicklung wurde auf eine aktuelle PHP-Version und die
Verwendung bewährter Konzepte und Komponenten gesetzt. 

[1]: http://jekyllrb.com
[2]: http://statamic.com
[3]: http://gohugo.io
[4]: http://getgrav.org
[5]: http://www.staceyapp.com

<ul>
    {% for item in site.pageList|filter("parentRoute=doc") %}
    <li><a href="{{ item.route }}">{{ item.title }}</a></li>
    {% endfor %}
</ul>
