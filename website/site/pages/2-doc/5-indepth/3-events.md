---
title: Event Listeners
layout: doc
---

# Event Listeners

Herbie is implementing a simple event dispatcher according to the PSR-14 specification.
During the application lifecycle the following events are dispatched in this order.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th style="width:8%">#</th>
            <th style="width:27%">Name</th>
            <th style="width:65%">Description</th>
        </tr>
    </thead>
    <tbody>
    {% for event in site.data.events %}
        <tr>
            <td>{{ event.order }}</td>
            <td>{{ event.name }}</td>
            <td>{{ event.desc }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>
