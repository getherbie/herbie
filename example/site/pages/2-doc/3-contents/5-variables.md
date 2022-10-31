---
title: Variables
layout: doc
---

# Variables

Herbie CMS traverses certain directories of the website and processes text files with a page properties block.
For each of these files, Herbie CMS generates different data and makes it available through the Twig template engine.

All variables can then be accessed in the layout and content files as normal Twig variables.
Here are some examples:

{% verbatim %}
    {{ route }}
    {{ site.language }}
    {{ site.data.persons }}
    {{ page.layout }}
    {{ page.tags }}
{% endverbatim %}

The details of these variables are listed below.

## Global variables

{{ snippet("@site/snippets/variables.twig", {type:"vars_global"}) }}


## Site variables

{{ snippet("@site/snippets/variables.twig",{type:"vars_site"}) }}


## Page variables

{{ snippet("@site/snippets/variables.twig", {type:"vars_page"}) }}
