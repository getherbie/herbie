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

{{ snippet(path="@site/snippets/simple_data.twig", type="events")|raw }}

**Filters**

{{ snippet(path="@site/snippets/simple_data.twig", type="filters")|raw }}

**Twig Filter**

{{ snippet(path="@site/snippets/simple_data.twig", type="twig_filters")|raw }}

**Twig Globals**

{{ snippet(path="@site/snippets/variables.twig", type="vars_global")|raw }}

**Twig Funktionen**

{{ snippet(path="@site/snippets/simple_data.twig", type="twig_functions")|raw }}

**Twig Tests**

{{ snippet(path="@site/snippets/simple_data.twig", type="twig_tests")|raw }}

**Plugins**

TBD
