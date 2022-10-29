---
title: Commands
layout: doc
---

# Commands

Herbie CMS is using the [Console Component](https://symfony.com/doc/current/components/console.html) for creating command-line commands, which can be used for any recurring task, such as cronjobs, imports, or other batch jobs.
The following commands are available by the command-line application.

<table class="pure-table pure-table-horizontal" width="100%">
    <thead>
    <tr>
        <th width="25%">Name</th>
        <th width="75%">Description</th>
    </tr>
    </thead>
    {% for command in site.data.commands %}
    <tr>
        <td>{{ command.name }}</td>
        <td>{{ command.desc }}</td>
    </tr>
    {% endfor %}
</table>
