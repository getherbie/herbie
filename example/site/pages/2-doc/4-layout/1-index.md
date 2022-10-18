---
title: Layout
layout: doc
twig: 0
---

# Layouts

Layoutdateien werden in Herbie CMS mit Hilfe der Template Engine Twig umgesetzt. 
Twig Templates sind recht einfach zu verstehen und sehr gut dokumentiert, siehe <https://twig.symfony.com/>.

Layoutdateien werden unter `site/layouts/default` abgelegt, wobei das letzte Pfadsegment für das Theme steht. 
Herbie CMS erwartet mindestens zwei Layoutdateien:

    site/layouts/default/
    |-- default.html        # das Default-Template
    └── error.html          # die Fehlerseite


Sinnvollerweise unterteilt man die Layouts in ein Haupttemplate und ein oder mehrere Subtemplates. 
Diese erben die Blöcke des Haupttemplates und können diese mit Inhalt befüllen.

    site/layouts/default/
    |-- default.html        # das Default-Template
    |-- twocols.html        # ein Zweispalter-Template
    |-- homepage.html       # das Homepage-Template
    |-- error.html          # die Fehlerseite
    └── main.html           # das Main-Template

Ein einfaches Haupttemplate sieht vielleicht so aus:

## Main-Template

    # main.html

    <!DOCTYPE html>
    <html>
        <head>
            <link rel="stylesheet" href="style.css" />
            <title>{% block title %}{% endblock %} - My Webpage</title>
        </head>
        <body>
            <div id="content">{% block content %}{% endblock %}</div>
            <div id="sidebar">{% block sidebar %}{% endblock %}</div>
            <div id="footer">Copyright 2015 by you.</div>
        </body>
    </html>

Im Haupttemplate wurden damit drei Blöcke (title, content, sidebar) definiert. 
Diese Blöcke enthalten aber noch keinen Inhalt.


## Sub-Template

Das Subtemplate "erbt" vom Haupttemplate und füllt die im Haupttemplate definierten Blöcke mit Inhalten.

    # default.html
    
    {% extends "main.html" %}
    
    {% block title %}Index{% endblock %}
    
    {% block content %}
        <h1>Index</h1>
        <p class="important">Welcome to my homepage!</p>
    {% endblock %}


Damit das Ganze auch dynamisch funktioniert, muss eine content-Funktion angewendet werden. 
Diese hat zur Aufgabe, die Inhalte eines Seitensegmentes auszugeben. 
Intern durchläuft die Funktion einen oder mehrere Formatierungsprozesse wie das Rendern von Twig oder das Parsen von Shortcode-, Markdown- oder Textile-Code.

Das angepasste (dynamisierte) Subtemplate sieht nun also so aus:

    # default.html
    
    {% extends "main.html" %}
    
    {% block title %}{{ page.title }}{% endblock %}
    
    {% block content %}  
        {{ content(0) }}
    {% endblock %}


Sowohl der Block `title` für den Seitentitel als auch der Block `content` für den Seiteninhalt werden dynamisch abgefüllt. 
Möchte man neben der normalen Inhaltsspalte eine Sidebar platzieren, kannst man dies wie folgt machen.

    # twocolumn.html
     
    {% extends "main.html" %}
    
    {% block title %}{{ page.title }}{% endblock %}
    
    {% block content %}  
        {{ content(0) }}
    {% endblock %}     
    
    {% block sidebar %}  
        {{ content(1) }}
    {% endblock %}


Über den Seiteneigenschaften-Block wird nun für jede Seite eines der vorbereiteten Layouts angewendet.

    ---
    title: Meine Zweispalter-Seite
    layout: twocolumn.html
    ---


Wie die Seiteninhalte formatiert sein müssen und weitere Informationen findet man im Kapitel [Inhalte](doc/content). 

Ein anderes gutes Anschauungs-Beispiel stellen die Layoutdateien dieser Website dar. 
Diese sind auf GitHub erreichbar unter <https://github.com/getherbie/website/>.
