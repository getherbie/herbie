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

Im Layoutdateien

    {{ page.title }}
    {{ page.layout }}

In Seitendateien

    [[page.title]]
    [[page.layout]]    

**Erlaubte Dateiendungen**<br>
txt, markdown, md, textile, htm, html

**Homepage**<br>
index.md

**Inhaltssegmente**

    --- 1 ---
    --- 2 ---
    --- 3 ---

**Inhaltssegmente im Layout** ausgeben

{% verbatim %}
    {{ content() }}
    {{ content(1) }}
    {{ content(2) }}
{% endverbatim %}


**Hooks**

[include path="@site/snippets/simple_data.twig" type="hooks" field="desc"]


**Twig Funktionen**

[include path="@site/snippets/simple_data.twig" type="twig_functions" field="desc"]


**Twig Filter**

[include path="@site/snippets/simple_data.twig" type="twig_filters" field="desc"]


**Twig Tests**

[include path="@site/snippets/simple_data.twig" type="twig_tests" field="desc"]


**Plugins**

[include path="@site/snippets/simple_data.twig" type="plugins" field="text"]
