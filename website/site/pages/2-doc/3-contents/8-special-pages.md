---
title: Special Pages
layout: doc
twig: false
---

# Special Pages

Some pages need to keep their file extension in the URL, for example XML, TXT or RSS files.
Set `keep_extension: true` in the frontmatter for these.
These examples help search engines discover and index your content (SEO) and provide feeds or crawler instructions.

## sitemap.xml

Lists all pages for search engines to crawl and index (SEO).

Create a page `site/pages/sitemap.xml`:

    ---
    title: Sitemap
    keep_extension: true
    type: xml
    ---

    <?xml version="1.0" encoding="UTF-8"?>
    <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        {% for item in site.page_list %}
        <url>
            <loc>{{ url_abs(item.route) }}</loc>
        </url>
        {% endfor %}
    </urlset>

## robots.txt

Tells crawlers which paths to access and points to the sitemap (SEO).

Create a page `site/pages/robots.txt`:

    ---
    title: Robots
    keep_extension: true
    type: txt
    ---

    User-agent: *
    Disallow:
    Sitemap: {{ url_abs('sitemap.xml') }}

## atom.xml

Provides an Atom feed of your content for feed readers and aggregators.

Create a page `site/pages/atom.xml`:

    ---
    title: Atom
    keep_extension: true
    type: xml
    ---

    <?xml version="1.0" encoding="UTF-8"?>
    <feed xmlns="http://www.w3.org/2005/Atom">
        ...
    </feed>
