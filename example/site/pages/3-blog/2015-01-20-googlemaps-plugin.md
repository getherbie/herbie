---
title: Google Maps Plugin
layout: blog
excerpt: Mit dem Google Maps Plugin bettest du Karten ganz einfach in deine Website ein.  
categories: [Feature,Plugin]
author: Herbie
image: google-maps-plugin.gif
---

# Google Maps Plugin

Mit dem Google Maps Plugin bettest du Karten ganz einfach in deine Website ein. Falls der Browser kein JavaScript
unterstützt, wird als Fallback ein statisches Google Maps-Bild eingebunden. Aktuell steht ein Shortcode zur Verfügung,
mit der zur angegebenen Adresse der passende Kartenausschnitt angezeigt wird. Hier sind ein paar Beispiele:

    [[googlemaps address="Eiffelturm, Paris" zoom=17]]
    [[googlemaps address="Kolloseum, Rom, Italien" zoom=16 type="hybrid"]]
    [[googlemaps address="Pyramiden von Gizeh" zoom=16 type="satellite"]]
    
Und so sehen die eingebetteten Karten aus:    

[googlemaps address="Eiffelturm, Paris" zoom=17]
Adresse: Eiffelturm, Paris | Zoom: 17

[googlemaps address="Kolloseum, Rom, Italien" zoom=16 type="hybrid"]
Adresse: Kolloseum, Rom, Italien | Zoom: 16 | Typ: Hybrid

[googlemaps address="Pyramiden von Gizeh" zoom=16 type="satellite"]
Adresse: Pyramiden von Gizeh | Zoom: 15 | Typ: Satellite

Weitere Funktionen sind geplant. Details zum Plugin und ein paar Beispiele findest du in der 
[link dokumentation/plugins/googlemaps text="Dokumentation"].
