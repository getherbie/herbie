---
title: Adding a Sitemap for Search Engines
type: recipe
layout: recipe
date: 2022-11-23
categories: [SEO]
---

# Adding a sitemap for web search engines

An XML sitemap is an essential part of helping search engines understand the architecture of your website. 
Sitemaps are also one of the fastest ways to perform search engine optimization.

With a sitemap, you tell the web crawler of web search engines which pages can be crawled and added to their indexes. 
The crawler will learn from this and prioritize the URLs over other undocumented links in the sitemap.

In this recipe, you will learn how to add a sitemap to your Herbie website.

## Sitemap structure

First of all, let's see what a sitemap should look like.

Looking at the [Wikipedia article about sitemaps](https://en.wikipedia.org/wiki/Sitemaps) we get the following:

~~~xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://example.com/doc</loc>
   <lastmod>2018-06-04</lastmod>
  </url>
</urlset>
~~~

To learn more about the sitemap formats, check out [Google's article on Sitemap formats](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap#sitemapformat).

## Adding a new page

First, add a new page called `sitemap.xml` in the `site.pages` folder with the following page properties.

    ---
    layout: ""
    content_type: text/xml
    keep_extension: true
    ---

## Creating the XML entries

Then, let's add all the pages we want to be found and crawled.
We don't do this manually, of course.
Instead we retrieve a page list and iterate over all pages and output their URL and modification date time in the XML document.

{% verbatim %}
~~~xml
<?xml version="1.0" encoding="UTF-8" ?>        
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{% for page in site.pageList %}
<url>
    <loc>{{ h_url_abs(page.route) }}</loc>
    {% if(item.date) %}
        <lastmod>{{ page.date|date("c") }}</lastmod>
    {% endif %}
</url>
{% endfor %}
</urlset>
~~~        
{% endverbatim %}

This will give us the following result:

~~~xml
<?xml version="1.0" encoding="UTF-8" ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com</loc>
        <lastmod>2022-11-19T14:51:23+00:00</lastmod>
    </url>
    <url>
        <loc>https://example.com/doc</loc>
        <lastmod>2022-11-19T11:04:15+00:00</lastmod>
    </url>
    <!-- some more entries -->
</urlset>
~~~

That's it. 
We now get the desired sitemap!

## Adding a robots.txt file

Another good SEO addition is the `robots.txt` file, also known as the *robots exclusion standard* or *robots exclusion protocol*.
This file is generally read first by crawlers and describes rules as to which pages the crawler may visit for crawling or not.
In there, we can also note the sitemap location.
The [Wikipedia article about robots.txt](https://en.wikipedia.org/wiki/Robots_exclusion_standard) contains some more information.

So, create a new page `robots.txt` in the `site/pages` folder with the following content.

{% verbatim %}
    ---
    layout: ""
    content_type: text/plain
    keep_extension: true
    ---
    User-agent: *
    Allow: /
    Sitemap: {{ h_url_abs('sitemap.xml') }}
{% endverbatim %}

In this case, we allow all robots on all paths.

The output looks like:

    User-agent: *
    Allow: /
    Sitemap: https://example.com/sitemap.xml

And this is how you create a sitemap for a Herbie website or blog. 

After the indexation, perform an internet search and see if you can find your pages in the search results!
