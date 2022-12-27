---
title: Twig Functions
layout: doc
---

# Twig Functions

Herbie is using [Twig Functions](https://twig.symfony.com/doc/3.x/functions/index.html) for generating content in layout and content files.
In addition to the built-in functions of Twig itself, the following functions are available.

{{ snippet("@site/snippets/twig_features.twig", {type:"twig_functions"}) }}

## Built-in Functions

In addition to the functions mentioned above, Twig's built-in functions are of course also available.
Please note that some of the functions may require the installation of additional Composer packages.

<ul>
{% for function in site.data.twig_functions_builtin %}
<li><a href="https://twig.symfony.com/doc/3.x/functions/{{ function.name }}.html" target="_blank">{{ function.name }}</a></li>
{% endfor %}
</ul>
