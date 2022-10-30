---
title: Filters
layout: doc
---

# Filters

Herbie CMS is using an intercepting filter inspired by [Laminas' FilterChain](https://docs.laminas.dev/laminas-filter/filter-chains/) for providing a mechanism to alter the workflow of the rendering process.
During the application lifecycle The following filters are used.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th style="width:25%">Name</th>
            <th style="width:75%">Description</th>
        </tr>
    </thead>
    <tbody>
    {% for filter in site.data.filters %}
        <tr>
            <td>{{ filter.name }}</td>
            <td>{{ filter.desc }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>
