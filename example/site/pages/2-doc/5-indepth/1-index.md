---
title: Indepth
layout: doc
---

# Indepth

Thanks to its plugin system Herbie CMS is highly customizable and brings support for:

<ul>
    {% for item in site.pageList|filter("route^=doc/indepth/") %}
    <li><a href="{{ url(item.route) }}">{{ item.title }}</a></li>
    {% endfor %}
</ul>
