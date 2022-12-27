---
title: Twig Filters
layout: doc
---

# Twig Filters

Herbie is using [Twig Filters](https://twig.symfony.com/doc/3.x/filters/index.html) for modifying variables in layout and content files.
The following filters are available.

{{ snippet("@site/snippets/twig_features.twig", {type:"twig_filters"}) }}

## Built-in Filters

In addition to the filters mentioned above, Twig's built-in filters are of course also available.
Please note that some of the filters may require the installation of additional Composer packages.

<ul>
{% for filter in site.data.twig_filters_builtin %}
<li><a href="https://twig.symfony.com/doc/3.x/filters/{{ filter.name }}.html" target="_blank">{{ filter.name }}</a></li>
{% endfor %}
</ul>
