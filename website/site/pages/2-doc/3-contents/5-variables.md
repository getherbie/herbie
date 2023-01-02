---
title: Variables
layout: doc
---

# Variables

Herbie crawls certain directories of the website and processes text files that contain a page properties block. 
For each of these files, different data is generated and made available in the templating system.

To avoid polluting the global namespace, this data is wrapped in three objects. 
These objects can be accessed in the layout and content files using normal Twig variables.

Here are some examples:

{% verbatim %}
    {{ content.default }}
    {{ page.title }}
    {{ page.layout }}
    {{ site.language }}
    {{ site.route }}
{% endverbatim %}

More details on these variables are provided below.

## Global variables

{{ snippet("@site/snippets/variables.twig", {type:"vars_global"}) }}

## Content variables

{{ snippet("@site/snippets/variables.twig", {type:"vars_content"}) }}

## Page variables

{{ snippet("@site/snippets/variables.twig", {type:"vars_page"}) }}

## Site variables

{{ snippet("@site/snippets/variables.twig",{type:"vars_site"}) }}
