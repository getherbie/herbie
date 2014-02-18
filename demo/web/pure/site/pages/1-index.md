---
title: Übersicht
date: 2013-12-27
---

{% for item in site.paginator %}

<section class="post">
    <header class="post-header">
        <!-- img class="post-avatar" alt="Eric Ferraiuolo&#x27;s avatar" height="48" width="48" src="/img/common/ericf-avatar.png" -->
        <h2 class="post-title">{{ link(item.route, item.title) }}</h2>
        <p class="post-meta">
            {{ item.date|strftime("%e. %B %Y") }}
            <!-- By <a class="post-author" href="#">Eric Ferraiuolo</a> under <a class="post-category post-category-js" href="#">JavaScript</a>-->
        </p>
    </header>
    <div class="post-description">
        <p>{{ item.excerpt }}</p>
    </div>
</section>

{% else %}

<div class="blog-post">
    <h2 class="blog-post-title">Sorry!</h2>
    <p class="blog-post-meta">Die aufgerufene Seite wurde nicht gefunden.</p>
</div>

{% endfor %}