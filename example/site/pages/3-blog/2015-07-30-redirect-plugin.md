---
title: Redirect Plugin
layout: blog
excerpt: Mit dem Redirect-Plugin kannst du auf Basis der Seiteneigenschaften eine Weiterleitung vornehmen. 
categories: [Feature,Plugin]
author: Herbie
type: blog
---

# Redirect Plugin

Mit dem neuen Redirect-Plugin kannst du auf einer beliebigen Seite eine Weiterleitung zu einer URL vornehmen.
Das Plugin installierst du mit Hilfe von Composer und aktivierst es dann in der Konfigurationsdatei.

Eine Weiterleitung definierst du über die Seiteneigenschaften. Im einfachsten Fall sieht das so aus: 
        
    ---
    title: Weiterleitung
    redirect: http://www.getherbie.org
    ---

Per Default wird der HTTP-Statuscode 302 gesendet. Möchtest Du einen anderen Statuscode senden, kannst du das
wie folgt machen:
 
    ---
    title: Weiterleitung
    redirect:
      url: http://www.getherbie.org
      status: 301
    ---
 
Weitere Informationen findest du in der [link dokumentation/plugins/redirect text="Dokumentation"].
