---
title: Übersicht
date: 2013-12-27
---

{% for item in site.paginator %}

<header class="post-header">
    <h2 class="post-title">{{ link(item.route, item.title) }}</h2>
    <p class="post-meta">
        {{ item.date|strftime("%e. %B %Y") }}
    </p>
</header>
<div class="post-description">
    <p>{{ item.excerpt }}</p>
</div>

{% else %}

<header class="post-header">
    <h2 class="post-title">Sorry!</h2>
    <p class="blog-post-meta">Die aufgerufene Seite wurde nicht gefunden.</p>
</header>

{% endfor %}