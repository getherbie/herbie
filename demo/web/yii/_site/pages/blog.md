---
title: Blog
date: 2013-12-27
---

{% for item in site.posts %}
<div class="blog-post">
    <h2 class="blog-post-title">{{ link(item.route, item.title) }}</a></h2>
    <p class="blog-post-meta">{{ item.date|strftime("%e. %B %Y") }}</p>
    <p>{{ item.excerpt }}</p>
</div>
{% else %}
<div class="blog-post">
    <h2 class="blog-post-title">Sorry!</h2>
    <p class="blog-post-meta">Die aufgerufene Seite wurde nicht gefunden.</p>
</div>
{% endfor %}