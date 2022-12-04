---
title: Recipes
layout: recipe
---

# Recipes

{% apply spaceless %}
    {% if routeParams.author %}
        <p>Filtered by Author "{{ routeParams.author }}"</p>
    {% elseif routeParams.category %}
        <p>Filtered by Category "{{ routeParams.category }}"</p>
    {% elseif routeParams.tag %}
        <p>Filtered by Tag "{{ routeParams.tag }}"</p>
    {% elseif routeParams.year and routeParams.month and routeParams.day %}
        <p>Filtered by Year/Month/Day "{{ routeParams.year }}-{{ routeParams.month }}-{{ routeParams.day }}"</p>
    {% elseif routeParams.year and routeParams.month %}
        <p>Filtered by Year/Month "{{ routeParams.year }}-{{ routeParams.month }}"</p>
    {% elseif routeParams.year %}
        <p>Filtered by Year "{{ routeParams.year }}"</p>
    {% endif %}
{% endapply %}

{% for item in site.pageList.filterItems('recipe', 'recipes', routeParams) %}
<p class="post-title"><b>{{ page_link(item.route, item.title) }}</b><br>
    {{ item.date|strftime("%e. %B %Y") }}
</p>
{% else %}
<p>There are no entries available.</p>
{% endfor %}
