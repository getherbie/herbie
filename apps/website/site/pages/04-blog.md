---
title: Blog
---

<div class="posts">
{% for item in site.posts %}
    <section class="post">
        <header class="post-header"><h2 class="post-title"><a href="{{ item.route }}">{{ item.title }}</a></h2></header>
        <div class="post-description">
            <p>{{ item.excerpt }}</p>
        </div>
    </section>
{% endfor %}
</div>