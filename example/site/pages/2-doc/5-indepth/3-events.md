---
title: Event Listeners
layout: doc
---

# Event Listeners

Herbie CMS is using event listeners inspired by [Laminas' EventManager](https://docs.laminas.dev/laminas-eventmanager) component.
During the application lifecycle the following events are triggered.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th style="width:25%">Name</th>
            <th style="width:75%">Description</th>
        </tr>
    </thead>
    <tbody>
    {% for event in site.data.events %}
        <tr>
            <td>{{ event.name }}</td>
            <td>{{ event.desc }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>
