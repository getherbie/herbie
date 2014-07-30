Herbie
======

Herbie is a file based CMS &amp; blog system.

**Herbie is not ready for production use yet.**

This fork uses a different markdown-parser based on [cebe/markdown](https://github.com/Netzweberei/markdown) to define a simple twitter-bootstrap grid like in https://github.com/dreikanter/markdown-grid

Menus must be defined with twig, as the menu()-function of this fork returns only a collection of menu-items, eg:

```
<!-- Twig Macros begin -->
{% macro sub_navigation(navigation) %}
{% import _self as macros %}
{% for item in navigation %}
    {% if item.children %}
    <li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">{{item.title}}<b class="caret"></b></a>
    <ul class="dropdown-menu">
        {{ macros.sub_navigation(item.children) }}
    </ul>
    </li>
    {% else %}
    <li><a href="{{item.url}}">{{item.title}}</a></li>
    {% endif %}
{% endfor %}
{% endmacro %}

{% import _self as macros %}

<!-- Twig Macros end -->
```

