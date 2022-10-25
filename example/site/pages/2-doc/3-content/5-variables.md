---
title: Variablen
layout: doc
---

# Variablen

Herbie CMS durchläuft bestimmte Verzeichnisse der Website und arbeitet Textdateien mit einem Seiteneigenschaften-Block ab.
Für jede dieser Dateien erzeugt Herbie CMS verschiedene Daten und macht diese über die Twig Template Engine verfügbar. 

Alle Variablen können in den Layout- und Inhaltsdateien als normale Twig-Variable abgerufen werden.
Hier sind einige Beispiele:

{% verbatim %}
    {{ route }}
    {{ site.language }}
    {{ site.data.persons }}
    {{ page.layout }}
    {{ page.tags }}
{% endverbatim %}

Nachfolgend sind die Details zu diesen Daten aufgelistet.

## Globale Variablen

{{ snippet("@site/snippets/variables.twig", {type:"vars_global"}) }}


## Site-Variablen

{{ snippet("@site/snippets/variables.twig",{type:"vars_site"}) }}


## Page-Variablen

{{ snippet("@site/snippets/variables.twig", {type:"vars_page"}) }}
