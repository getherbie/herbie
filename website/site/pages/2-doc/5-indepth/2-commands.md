---
title: Console Commands
layout: doc
---

# Console Commands

Herbie CMS is using [Console Components](https://symfony.com/doc/current/components/console.html) for creating command-line commands.
Console commands can be used for any recurring task, such as cronjobs, imports, or other batch jobs.
The following commands are available by the `herbie` command-line application.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th style="width:25%">Name</th>
            <th style="width:75%">Description</th>
        </tr>
    </thead>
    <tbody>
    {% for command in site.data.commands %}
        <tr>
            <td>{{ command.name }}</td>
            <td>{{ command.desc }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>

Herbie CMS console commands can be executed with the following syntax:

    php vendor/bin/herbie

This will give you a list of all available commands.
The output looks like this:

    -------------------
    HERBIE CMS CLI-Tool
    -------------------
    
    Usage:
    command [options] [arguments]
    
    Options:
    -h, --help            Display help for the given command. When no command is given display help for the list command
    -q, --quiet           Do not output any message
    -V, --version         Display this application version
    --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
    -n, --no-interaction  Do not ask any interactive question
    -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
    
    Available commands:
    clear-files  Clears asset, cache and log files
    completion   Dump the shell completion script
    help         Display help for a command
    list         List commands

The call to clear the caches then looks like this, for example.

    php vendor/bin/herbie clear-files

Additional information on how to execute commands can be found at <https://symfony.com/doc/current/components/console/usage.html>.
