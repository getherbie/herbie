---
title: Recipes
layout: recipe
---

# Recipes

{{ pages_filtered(routeParams) }}

{% set items = site.pageList.filterItems('recipe', 'recipes', routeParams) %}
{% for item in items %}
<p class="post-title"><b>{{ page_link(item.route, item.title) }}</b><br>
    {{ item.date|strftime("%e. %B %Y") }}
</p>
{% else %}
<p>There are no entries available.</p>
{% endfor %}
