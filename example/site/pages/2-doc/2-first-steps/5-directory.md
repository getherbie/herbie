---
title: Directory structure
layout: doc
---

# Directory structure

A Herbie CMS website is usually structured as follows:

    project
    ├── site
    ├── vendor
    └── web
        ├── assets
        ├── cache
        ├── media
        ├── .htaccess
        └── index.php


What these files and directories stand for can be seen in the following table:

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th style="width:35%">File/Directory</th>
            <th style="width:65%">Description</th>
        </tr>
    </thead>
    <tbody>
    {% for data in site.data.site_dir_basic %}
        <tr>
            <td>{{ data.name }}</td>
            <td>{{ data.desc }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>


## Site directory

Normally one works exclusively in the `site` directory of the web project.
This is usually structured as follows:

    site
    ├── assets
    ├── config
    │   └── main.yml
    ├── data
    │   └── persons.yml
    ├── extend
    │   ├── commands
    │   ├── events
    │   ├── filters
    │   ├── middlewares_app
    │   ├── middlewares_route
    │   ├── plugins
    │   ├── twig_filters
    │   ├── twig_functions
    │   ├── twig_globals
    │   └── twig_tests
    ├── pages
    │   ├── company
    │   │   ├── about-us.md
    │   │   ├── index.md
    │   │   ├── our-vision.md
    │   │   └── team.md
    │   ├── contact.md
    │   ├── index.md
    │   └── services.md
    ├── runtime
    │   ├── cache
    │   │   ├── system
    │   │   └── twig
    │   └── log
    └── themes
        └─ default
            ├── default.html
            └── error.html


The following table shows what each of these files and directories stand for:

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th style="width:35%">File/Directory</th>
            <th style="width:65%">Description</th>
        </tr>
    </thead>
    <tbody>
    {% for data in site.data.site_dir_extended %}
        <tr>
            <td>{{ data.name }}</td>
            <td>{{ data.desc }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>


## .htaccess file

If the option `components.urlManager.niceUrls` is enabled in the configuration, there must be an `.htaccess` file with the appropriate instructions in the `web` directory.

    RewriteEngine on
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . index.php

Thus, all page views are passed on to the central index file.
This is important for search engine optimization, for example, but also for visitors to the website.

Note: The above configuration is designed for Apache web server.
