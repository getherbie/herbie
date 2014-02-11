---
title: Übersicht
date: 2013-12-27
---

{% for item in site.posts %}
<div class="blog-post">
    <h2 class="blog-post-title"><a href="{{ item.route }}">{{ item.title }}</a></h2>
    <p class="blog-post-meta">{{ item.date|strftime("%e. %B %Y") }}</p>
    <p>{{ item.excerpt }}</p>
</div>
{% endfor %}
