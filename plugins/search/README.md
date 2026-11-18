# Herbie Search Plugin

Search is a [Herbie](http://github.com/getherbie) plugin that allows you to provide your website with a simple but useful search engine.

## Installation

The plugin is part of Herbie and needs no installation.
It is activated in the configuration file.

~~~php
return [
    // a comma-separated list of plugins to be activated
    'enabledPlugins' => 'search',
];
~~~

## Configuration

The following options are available in the configuration file.

~~~php
return [
    'plugins' => [        
        'search' => [
            // path to twig form template
            'formTemplate' => '@plugin/search/templates/form.twig',
            // path to twig results template
            'resultsTemplate' => '@plugin/search/templates/results.twig',
            // enable the internal search cache (default: true)
            'useCache' => true,
        ],
    ],
];
~~~

Please note that the above configuration is only part of the overall configuration.

## Behavior

- **Multi-term queries:** all search terms must match (AND logic).
- **Exact search:** append `?exact=1` to the URL or use the checkbox in the
  search form. The full query must then appear as a contiguous phrase.
- **Excluded pages:** pages with `no_search: true` or `hidden: true` are
  not indexed.

## Page properties

With the page property `no_search` individual pages can be excluded from the search.
The page properties for such a page look like this:

    ---
    title: My page
    no_search: true
    ---

A complete search page looks like this:

    ---
    title: Search
    cached: false
    hidden: true
    ---
    
    # Search
    
    {{ search_form() }}
    
    {{ search_results() }}

## Demo

A live demo can be viewed at <https://herbie.tebe.ch/search>
