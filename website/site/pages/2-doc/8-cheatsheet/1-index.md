---
title: Cheat Sheet
layout: doc
---

# Cheat Sheet

The cheat sheet is still under construction, but here is a first part.

**Page properties**

    ---
    title: Page title
    layout: default
    ---

Output page properties in layout and content files:

    {{ '{{' }} page.title }}
    {{ '{{' }} page.layout }}

**Allowed file extensions**<br>
htm, html, markdown, md, rss, rst, textile, txt, xml

**Homepage**<br>
index.md

**Content segments**

    --- default ---
    --- left ---
    --- right ---

Output content segments in layout files:

{% verbatim %}
    {{ content.default }}
    {{ content.left }}
    {{ content.right }}
{% endverbatim %}

**Event Listeners**

{{ h_snippet("@site/snippets/simple_data.twig", {type:"events"}) }}

**Twig Filters**

{{ h_snippet("@site/snippets/simple_data.twig", {type:"twig_filters"}) }}

**Twig Globals**

{{ h_snippet("@site/snippets/simple_data.twig", {type:"vars_global"}) }}

**Twig Functions**

{{ h_snippet("@site/snippets/simple_data.twig", {type:"twig_functions"}) }}

**Twig Tests**

{{ h_snippet("@site/snippets/simple_data.twig", {type:"twig_tests"}) }}

**Console Commands**

{{ h_snippet("@site/snippets/simple_data.twig", {type:"commands"}) }}

**Plugins**

{{ h_snippet("@site/snippets/simple_data.twig", {type:"plugins", enabled:"enabled"}) }}
