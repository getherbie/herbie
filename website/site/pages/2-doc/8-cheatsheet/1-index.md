---
title: Cheat Sheet
layout: doc
---

# Cheat Sheet

A quick reference for the most common Herbie features.

**Page properties**

~~~yaml
---
id: "@page/about/team.html"
title: Page title
layout: default
menu_title: Menu title
redirect: /about
hidden: false
format: markdown
type: page
tags: [intro]
categories: [docs]
authors: [John Doe]
date: 2024-01-15
created: 2024-01-01
modified: 2024-06-01
cached: false
keep_extension: false
content_type: text/html
excerpt: Short teaser
twig: true
parent_id: "@page/index.md"
parent_route: ""
path: /path/to/site/pages/about/team.html
route: about/team
---
~~~

Output page properties in layout and content files:

~~~twig
{{ '{{' }} page.title }}
{{ '{{' }} page.layout }}
~~~

**Allowed file extensions**<br>
htm, html, markdown, md, rss, rst, textile, txt, xml

**Homepage**<br>
index.md

**Content segments**

~~~text
--- default ---
--- left ---
--- right ---
~~~

Output content segments in layout files:

{% verbatim %}
~~~twig
{{ content.default|raw }}
{{ content.left|raw }}
{{ content.right|raw }}
~~~
{% endverbatim %}

**Event Listeners**

{{ snippet('@site/snippets/simple_data.twig', {type:'events'}) }}

**Twig Filters**

{{ snippet('@site/snippets/simple_data.twig', {type:'twig_filters'}) }}

**Twig Globals**

{{ snippet('@site/snippets/simple_data.twig', {type:'vars_global'}) }}

**Twig Functions**

{{ snippet('@site/snippets/simple_data.twig', {type:'twig_functions'}) }}

**Twig Tests**

{{ snippet('@site/snippets/simple_data.twig', {type:'twig_tests'}) }}

**Console Commands**

{{ snippet('@site/snippets/simple_data.twig', {type:'commands'}) }}

**Plugins**

{{ snippet('@site/snippets/simple_data.twig', {type:'plugins', enabled:'enabled'}) }}

**Routing**

Routes are generated from page file paths relative to `site/pages/`. Numeric sorting prefixes (`2-doc`) and the file extension are removed; `index` files map to the directory itself.

~~~text
site/pages/2-doc/1-introduction/1-index.md   ->  /doc/introduction
site/pages/2-doc/3-contents/3-create-pages.md ->  /doc/contents/create-pages
~~~

**Special Pages**

Pages such as `sitemap.xml`, `robots.txt` or `atom.xml` must keep their extension in the URL. Set `keep_extension: true` in the frontmatter.

~~~yaml
keep_extension: true
~~~

**Aliases**

Aliases map shortcut names to filesystem paths:

~~~text
@app       -> project root
@site      -> site/
@page      -> site/pages/
@media     -> site/media/
@web       -> web/
@asset     -> site/assets/
@plugin    -> plugins/
@vendor    -> vendor/
@snippet   -> templates/snippets/
~~~

**Configuration**

Global defaults live in `config/defaults.php`; project-specific overrides go in `site/config/main.php`.

~~~php
<?php
return [
    'language' => 'en',
    'theme' => 'default',
    'enabledPlugins' => 'twig,markdown',
    'components' => [
        'urlManager' => ['niceUrls' => true],
    ],
];
~~~

**Layouts / Themes**

Layouts are Twig files in `site/themes/<theme>/<layout>.twig`; select one with `layout:` in the frontmatter.

{% verbatim %}
~~~twig
{% extends 'main.twig' %}

{% block content %}
    {{ content.default|raw }}
{% endblock %}
~~~
{% endverbatim %}

**Data Files**

Files in `site/data/` (YAML or JSON) are available as `site.data.<name>`.

{% verbatim %}
~~~twig
site/data/menu.yml   ->  {{ site.data.menu }}
~~~
{% endverbatim %}

**Middlewares**

Application middlewares run for every request; route middlewares only for matching routes.

~~~php
$app->addApplicationMiddleware($middleware);
$app->addRouteMiddleware('news/(.+)', $middleware);
~~~

See [Middlewares](doc/indepth/middlewares) for the middleware chain and examples.

**Translations**

Translation files live in `messages/<language>.php` and are used with the `translate` Twig function.

{% verbatim %}
~~~twig
{{ translate('Hello') }}
~~~
{% endverbatim %}

**Snippets**

Snippets are Twig files in `templates/snippets/` and can be included with `snippet()`.

{% verbatim %}
~~~twig
{{ snippet('@snippet/example.twig') }}
~~~
{% endverbatim %}

**Media / Assets**

Media lives in `site/media/` (`@media`), theme assets in `site/assets/`. Herbie serves `@media` under `/media/...` (`urls.media`): types a browser can display are opened inline, everything else is downloaded. `?download` forces a download. Paths that do not start with `/media/` are relative to the web root and served by the web server.

{% verbatim %}
~~~twig
{{ image('/media/photo.jpg') }}                {# <img src="/media/photo.jpg"> #}
{{ image('/logo.png') }}                       {# <img src="/logo.png"> from the web root #}
{{ link_download('manual.pdf', 'Handbuch') }}  {# /media/manual.pdf?download #}
{{ file_url('/media/photo.jpg') }}             {# /media/photo.jpg #}
{{ file_url('/media/manual.pdf', download: true) }}    {# /media/manual.pdf?download #}
~~~
{% endverbatim %}

In plain Markdown or HTML use the URL directly (`![Logo](/media/logo.png)`) when the site runs in the web root, otherwise `{{ file_url('/media/logo.png') }}`.

**Page Types**

Valid values for `type:` are `page`, `navigation`, `search`, `rss`, `data` and `site`.

~~~yaml
type: navigation
~~~

**Variables**

Twig provides the current page, site data and content segments.

{% verbatim %}
~~~twig
{{ page.title }}
{{ site.data.menu }}
{{ content.default|raw }}
{{ site.route_params.category }}
~~~
{% endverbatim %}

**Menus / Navigation**

Menus are built from page lists or data files with the `menu_*` functions.

{% verbatim %}
~~~twig
{{ menu_list() }}
~~~
{% endverbatim %}

**URLs & Links**

Use the URL and link helpers instead of hard-coded paths.

{% verbatim %}
~~~twig
{{ link_page('about', 'About') }}
{{ link_download('image.jpg') }}
{{ url_abs('sitemap.xml') }}
~~~
{% endverbatim %}

**PageList / Query Builder**

`site.page_list` filters, sorts and groups pages.

{% verbatim %}
~~~twig
{{ site.page_list.query().where('layout=doc').order('title').count() }}
~~~
{% endverbatim %}

**Caching & Debug**

Use `cached: false` for uncached pages and `HERBIE_DEBUG=1` for verbose errors. Clear caches with `./herbie clear-files`.

~~~yaml
cached: false
~~~

**Plugin Configuration**

Plugin options are set under `plugins.<name>` in `site/config/main.php`.

~~~php
'plugins' => [
    'markdown' => ['flavor' => 'gfm'],
    'search' => [
        // path to twig form template
        'formTemplate' => '@plugin/search/templates/form.twig',
        // path to twig results template
        'resultsTemplate' => '@plugin/search/templates/results.twig',
        // enable the internal search cache (default: true)
        'useCache' => true,
    ],
],
~~~
