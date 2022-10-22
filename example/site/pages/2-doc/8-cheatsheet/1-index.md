---
title: Cheat Sheet
layout: doc
---

# Cheat Sheet

Das Cheat Sheet befindet sich noch im Aufbau, aber hier ist schon mal ein erster Teil.

**Seiteneigeschaften**

    ---
    title: Seitentitel
    layout: default.html
    ---

**Seiteneigeschaften ausgeben**

Im Layout- und Seitendateien

    {{ '{{' }} page.title }}
    {{ '{{' }} page.layout }}

**Erlaubte Dateiendungen**<br>
htm, html, markdown, md, rss, rst, textile, txt, xml

**Homepage**<br>
index.md

**Inhaltssegmente**

    --- default ---
    --- left ---
    --- right ---

**Inhaltssegmente im Layout** ausgeben

{% verbatim %}
    {{ content("default") }}
    {{ content("left") }}
    {{ content("right") }}
{% endverbatim %}

**Events**

{{ snippet("@site/snippets/simple_data.twig", {type:"events"}) }}

**Filters**

{{ snippet("@site/snippets/simple_data.twig", {type:"filters"}) }}

**Twig Filter**

{{ snippet("@site/snippets/simple_data.twig", {type:"twig_filters"}) }}

**Twig Globals**

{{ snippet("@site/snippets/variables.twig", {type:"vars_global"}) }}

**Twig Funktionen**

{{ snippet("@site/snippets/simple_data.twig", {type:"twig_functions"}) }}

**Twig Tests**

{{ snippet("@site/snippets/simple_data.twig", {type:"twig_tests"}) }}

**Plugins**

TBD
