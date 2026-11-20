---
title: Special Pages
layout: doc
twig: false
---

# Special Pages

Some pages need to keep their file extension in the URL, for example XML, TXT or RSS files.
Set `keep_extension: true` in the page properties block for these.
Set `layout: ""` so the content is not wrapped in an HTML layout, and `content_type` to the MIME type of the response.
These examples help search engines discover and index your content (SEO) and provide feeds or crawler instructions.

## sitemap.xml

Lists all pages for search engines to crawl and index (SEO).

Create a page `site/pages/sitemap.xml`:

~~~xml
---
title: Sitemap
layout: ""
keep_extension: true
content_type: text/xml
---

<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    {% for item in site.page_list %}
    <url>
        <loc>{{ url_abs(item.route) }}</loc>
        {% if(item.date) %}
            <lastmod>{{ item.date|date("c") }}</lastmod>
        {% endif %}
    </url>
    {% endfor %}
</urlset>
~~~

## robots.txt

Tells crawlers which paths to access and points to the sitemap (SEO).

Create a page `site/pages/robots.txt`:

~~~text
---
title: Robots
layout: ""
keep_extension: true
content_type: text/plain
---

User-agent: *
Disallow:
Sitemap: {{ url_abs('sitemap.xml') }}
~~~

## atom.xml

Provides an Atom feed of your content for feed readers and aggregators.

Create a page `site/pages/atom.xml`:

~~~xml
---
title: Atom Feed
layout: ""
content_type: text/xml
keep_extension: true
no_search: true
---

<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>My Site Feed</title>
    <subtitle>The latest posts from my site.</subtitle>
    <link href="{{ url_abs('atom.xml') }}" rel="self" />
    <link href="{{ url_abs('/') }}" />
    <id>{{ url_abs('/') }}</id>
    <updated>{{ "now"|date('r') }}</updated>
    {% for item in site.page_list %}
        {% if item.title != '' %}
        <entry>
            <title>{{ item.title }} / My Site</title>
            <link href="{{ url_abs(item.route) }}"/>
            <id>{{ url_abs(item.route) }}</id>
            {% if item.date %}<published>{{ item.date|date("c") }}</published>{% endif %}
            {% if item.date %}<updated>{{ item.date|date("c") }}</updated>{% endif %}
            {% if item.abstract %}<summary>{{ item.abstract }}</summary>{% endif %}
        </entry>
        {% endif %}
    {% endfor %}
</feed>
~~~
