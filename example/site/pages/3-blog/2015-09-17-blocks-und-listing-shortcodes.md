---
title: Blocks- und Listing-Shortcodes
layout: blog
excerpt: 'Mit Version 0.8.2 wurden zwei neue Shortcodes eingeführt, die es in sich haben. Mit "Blocks" erstellst du komplexe Layouts und mit "Listings" Listendarstellungen beliebiger Seiten.' 
categories: [Feature,Release]
author: Herbie
type: blog
---

# Blocks- und Listing-Shortcodes

Mit Version 0.8.2 wurden zwei neue Shortcodes eingeführt: Blocks und Listing. Mit dem `blocks`-Shortcode wird es 
möglich, Seiten aus mehrenen Blocks (=spezielle Unterseiten) zusammen zu bauen. Somit können mehrspaltige oder andere 
komplexe Layouts einfach umgesetzt werden. Blocks sind im Prinzip normale Seiten mit eigenem Layout.

Um Blocks in einer deiner Seite zu nutzen, wendest du den Shortcode und allfällige Parameter wie folgt an.

    [[blocks sort="title|desc" shuffle="false"]]
    
Neben den Blocks gibt es noch einen weiteren nützlichen Shortcode. Mit dem `listing`-Shortcode gibst du eine 
Auflistung von Seiten aus. Damit kannst du dir im Handumdrehen ein einfaches Newsplugin oder ein anderes ähnliches 
Plugin zusammen bauen, und zwar nur aus normalen Seiten. Der Shortcode sieht wie folgt aus:

    [[listing filter="parentRoute|news" sort="title|desc" limit="5"]]

Das tolle daran sind die eingebaute Paginierung und die Möglichkeit, die Auflistung zu sortieren, zu begrenzen oder auch 
per Zufall sortieren zu lassen. Die Ausgabe kannst du mittels eines eigenen Twig-Templates auch komplett selber steuern. 
Damit hast du die volle Kontrolle über den HTML-Code!

Mehr zu den neuen Shortcodes und den vielen anderen nützlichen Shortcodes findest du in der
[link dokumentation/inhalte/shortcodes text="Dokumentation"].
