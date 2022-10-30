---
title: Create Pages
layout: doc
---

# Create pages

## The pages directory

All pages of a Herbie CMS website are stored in the directory `site/pages` as plain text files.
These text files can be plain text, markdown, textile or HTML files.

In order for Herbie CMS to recognize these files and convert them to HTML, they must contain a block of page properties in the header of the file.


## Allowed files

Herbie CMS currently supports the following file types:

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th style="width:35%">Extension</th>
            <th style="width:65%">Formatter</th>
            <th style="width:65%">Conversion</th>
        </tr>
    </thead>
    <tbody>
    {% for data in site.data.page_extensions %}
        <tr>
            <td>.{{ data.extension }}</td>
            <td>{{ data.formatter }}</td>
            <td>{{ data.conversion }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>

When parsing the content, the formatter corresponding to the file extension is used.
Thus, the content of a file with the extension .md is converted by the Markdown parser.
And a file with the extension .textile by a Textile parser.


## Creating a page

To create a new page, add a new file with one of the allowed extensions in the `site/pages` directory.
You must respect the following rules:

- only lowercase letters, numbers, underscores and hyphens
- no umlauts, special or control characters
- no spaces

How you name the file will affect the web address and how the page is viewed in the browser.
If you follow the above rules, you will get nicely readable and working links to the subpages of the website.


## Homepage

As the only file in the `site/pages` directory, Herbie CMS expects an index file with one of the above extensions.
This file takes over the function of the homepage or start page and is displayed when *http://www.example.com* is called in the browser.
If the index file is missing, a 404 error page is displayed.


## Named text files

The easiest way to add pages is to add a text file with an appropriate name in the pages directory.
For a site with a home page, an about page, and a contact page, the page directory and their corresponding URLs would look like this:

    site/pages
    ├── index.md        # https://example.com
    ├── about.md        # https://example.com/about
    └── contact.md      # https://example.com/contact


## Named folders with index files

You can do it this way, and there is nothing wrong with it at all.
However, often you want to add more pages or group existing pages into a topic.
For example, if a team page, a vision page, and a route page are added to the above website, the page index might look like this:

    site/pages
    ├── index.md        # https://example.com
    ├── about/
    │   ├── index.md    # https://example.com/about
    │   ├── team.md     # https://example.com/about/team
    │   └── vision.md   # https://example.com/about/vision
    ├── contact/
    │   ├── index.md    # https://example.com/contact
    │   └── route.md    # https://example.com/contact/route
    └── index.md        # https://example.com


Which way is better depends largely on the type of website.
For small websites, named text files without further sub folders are enough.
For large websites, there is no way around additional sub folders and text files.


## Visibility and sorting

By prefixing files with a number followed by a hyphen, you can control sorting and visibility in menus.
It looks like this, for example:

    site/pages
    ├── 1-index.md      # visible and sorted
    ├── 2-about-us.md   # visible and sorted
    ├── 3-contact.md    # visible and sorted
    ├── sitemap.md      # not visible
    └── imprint.md      # not visible

The pages *index*, *about-us* and *contact* are visible in menus and sorting is defined.
The pages *sitemap* and *imprint* are not visible in menus and sorting is therefore not relevant.

**Note:** For folders, the preceding number only affects the sorting, but not the visibility.

The visibility of folders is controlled by the index file in the folders.

## Disable page or folder

Sometimes you want to disable a page or a whole folder.
This can be done by prefixing the name of the page or folder with an underscore.
Such pages and folders are not taken into account when scanning the file system.

    site/pages
    ├── index.md
    ├── _about/         # The folder incl. subpages is disabled
    │   ├── index.md
    │   ├── team.md
    │   └── vision.md
    └── contact/
        ├── index.md
        └── _route.md   # The page is disabled

This is helpful, for example, when pages should be temporarily disabled.
