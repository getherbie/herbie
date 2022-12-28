---
title: Recipes
layout: recipe
---

# Recipes

{% apply spaceless %}
    {% if site.route_params.author %}
        <p>Filtered by Author "{{ site.route_params.author }}"</p>
    {% elseif site.route_params.category %}
        <p>Filtered by Category "{{ site.route_params.category }}"</p>
    {% elseif site.route_params.tag %}
        <p>Filtered by Tag "{{ site.route_params.tag }}"</p>
    {% elseif site.route_params.year and site.route_params.month and site.route_params.day %}
        <p>Filtered by Year/Month/Day "{{ site.route_params.year }}-{{ site.route_params.month }}-{{ site.route_params.day }}"</p>
    {% elseif site.route_params.year and site.route_params.month %}
        <p>Filtered by Year/Month "{{ site.route_params.year }}-{{ site.route_params.month }}"</p>
    {% elseif site.route_params.year %}
        <p>Filtered by Year "{{ site.route_params.year }}"</p>
    {% endif %}
{% endapply %}

{% for item in site.page_list.query().where('layout=recipe', 'title!=Recipes') %}
<p class="post-title"><b>{{ link_page(item.route, item.title) }}</b><br>
    {{ item.date|date("d. F Y") }}
</p>
{% else %}
<p>There are no entries available.</p>
{% endfor %}
