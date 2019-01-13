---
title: Version 0.8.0 veröffentlicht
layout: blog
excerpt: Das 0.8.0 Release bringt deutliche Verbesserungen. Viele Funktionen sind hinzugekommen, Shortcode, Twig und Markdown wurden als Systemplugins in den Core verschoben, der Code optimiert und die Dokumentation vervollständigt.
categories: [Feature,Release]
author: Herbie
---

# Version 0.8.0 veröffentlicht

Mit dem Release der Version 0.8.0 hat sich in Herbie einiges getan. Es sind viele neue Funktionen hinzugekommen,
der Code wurde an vielen Stellen optimiert und die Dokumentation vervollständigt. Hier sind die wichtigsten Änderungen:


## Shortcodes

Shortcodes sind nun fester Bestandteil von Herbie. Sie sind für Formatierungen, komplexe Logik und vieles mehr in 
den Seiteninhalten zuständig. Mit Herbie kommen viele System-Shortcodes, die dir das Leben deutlich erleichtern.
 
Gleichzeitig wurde die Funktion von Twig im Wesentlichen auf das Parsen der Layoutdateien begrenzt. Kurz: Shortcodes 
nutzt man also in Seiteninhalten, Twig nutzt man in HTML-Layoutdateien.


## DI-Container

Mit Hook\DI wurde ein Dependency Injection Container implementiert, der es dem Systemintegrator und Plugin-Entwickler 
erlaubt, in der PHP-Umgebung auf alle Services zuzugreifen.

Benötigst du das Konfigurations-Objekt, bekommst du es auf diesem Weg:

    Herbie\DI::get('Config');

Möchtest du Zugriff auf das Shortcode-Objekt, kannst du das so bewerkstelligen:

    Herbie\DI::get('Shortcode');


## Hook-System

Herbie verfügt jetzt über ein einfaches aber mächtiges Hooksystem, mit dem du an vielen Stellen Einfluss auf das 
Verhalten des Programmablaufs nehmen kannst. Einem Programmpunkt fügst du eine PHP-Funktion wie folgt hinzu:

    Herbie\Hook::attach('shortcodeInitialized', ['GistPlugin', 'addShortcode']);

Mehr Informationen zu den Hooks findest du in der [link dokumentation/hooks text="Hook-Dokumentation"].


## Plugins

Die 3rd-Party Plugins wurden ebenfalls deutlich vereinfacht und nutzen nun das neue Hook-System. Ein einfaches Plugin 
kannst du somit mit ein paar Zeilen PHP-Code erstellen.

Aktuelle Informationen findest du in der [link dokumentation/plugins text="Plugin-Dokumentation"].


## Systemplugins

Viele Funktionalitäten von Herbie wurden aus dem Kern genommen und in Systemplugins ausgelagert. Der Programmcode wurde 
dadurch deutlich vereinfacht und gleichzeitig entschlackt. Durch dieses Refactoring sind die neuen Systemplugins 
Twig, Shortcode, Markdown und Textile entstanden.
