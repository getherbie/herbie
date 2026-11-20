---
title: What can be extended
layout: doc
---

# What can be extended

## Extension points

An extension point is a place in the application lifecycle where you can add your own code. Herbie provides these extension points:

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th>Extension point</th>
            <th>Typical use</th>
            <th>Difficulty</th>
            <th>Frequency</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Console command</td>
            <td>Provide functionality in the CLI</td>
            <td>Medium</td>
            <td>Rare</td>
        </tr>
        <tr>
            <td>Event listener</td>
            <td>React to system events</td>
            <td>Medium</td>
            <td>Rare</td>
        </tr>
        <tr>
            <td>Application middleware</td>
            <td>Modify HTTP requests and responses</td>
            <td>Medium</td>
            <td>Rare</td>
        </tr>
        <tr>
            <td>Route middleware</td>
            <td>Modify HTTP requests and responses for matching routes</td>
            <td>Medium</td>
            <td>Rare</td>
        </tr>
        <tr>
            <td>Twig filter</td>
            <td>Transform values in templates</td>
            <td>Very easy</td>
            <td>Frequent</td>
        </tr>
        <tr>
            <td>Twig global</td>
            <td>Provide a global value or helper object</td>
            <td>Very easy</td>
            <td>Frequent</td>
        </tr>
        <tr>
            <td>Twig function</td>
            <td>Generate content in templates</td>
            <td>Very easy</td>
            <td>Frequent</td>
        </tr>
        <tr>
            <td>Twig test</td>
            <td>Evaluate boolean conditions in templates</td>
            <td>Very easy</td>
            <td>Frequent</td>
        </tr>
    </tbody>
</table>

For details on specific extension points, see [Commands](doc/indepth/commands), [Event Listeners](doc/indepth/events), [Middlewares](doc/indepth/middlewares), [Twig Filters](doc/indepth/twig-filters), [Twig Functions](doc/indepth/twig-functions), [Twig Globals](doc/indepth/twig-globals), and [Twig Tests](doc/indepth/twig-tests).

## Choosing a method

There are four ways to add extensions, with increasing flexibility and complexity.

- **Files in site/extend**: Best for small, project-specific extensions that should be quick to add.
- **Register in index.php**: Useful when extensions should be registered explicitly in `index.php`, for example alongside other startup code.
- **Plugin class**: Suitable for grouping related extensions in one class, with optional configuration and dependency checks.
- **Composer package**: Use this when an extension should be versioned, shared, and installed via Composer.
