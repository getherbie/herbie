---
title: Images, files and downloads
layout: doc
---

# Images, files and downloads

Images, PDFs, videos and other files that you link from your content live in the directory
`site/media/` (alias `@media`). Herbie serves them under the URL `/media/...`.

~~~text
site/media/logo.png        →  /media/logo.png
site/media/docs/manual.pdf →  /media/docs/manual.pdf
~~~

Files in the public web directory (`web/`) are delivered directly by the web server, for example
`web/favicon.ico` as `/favicon.ico`.

## The rule

> `/media/<path>` is the file. It is opened the way the browser can open it, otherwise it is downloaded.
> With `?download` you force a download.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th style="width:40%">Request</th>
            <th style="width:60%">Result</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>/media/logo.png</code>, <code>/media/manual.pdf</code></td>
            <td>Opened inline in the browser.</td>
        </tr>
        <tr>
            <td><code>/media/manual.pdf?download</code></td>
            <td>Forced download.</td>
        </tr>
        <tr>
            <td><code>/media/prices.xlsx</code>, <code>/media/logo.svg</code>, <code>/media/page.html</code></td>
            <td>Always a download: the browser cannot display the file type, or the file can contain scripts (SVG, HTML).</td>
        </tr>
        <tr>
            <td><code>/media/.env</code>, <code>/media/script.php</code></td>
            <td>Not found (404). Hidden files and PHP files are never delivered.</td>
        </tr>
    </tbody>
</table>

Only the presence of the parameter counts, not its value: `?download=0` forces a download as well.

An SVG file is still displayed when you use it as an image (`<img src="/media/logo.svg">`,
`![Logo](/media/logo.svg)` or `image('/media/logo.svg')`). Only opening its URL directly downloads
it, and an `<iframe>` or `embed()` does not display it.

Types that are opened inline: `avif`, `css`, `gif`, `ico`, `jpeg`, `jpg`, `js`, `json`, `mp3`,
`mp4`, `pdf`, `png`, `txt` and `webp`.

Herbie supports HTTP range requests with a single byte range (`Range: bytes=0-999`). Seeking in
video and audio files and resuming interrupted downloads therefore work. A request with several
ranges in one header is answered with the whole file. Large video files are streamed and not
loaded into memory.

## Markdown and HTML

Write the URL directly:

~~~markdown
![Logo](/media/logo.png)
[Open the manual](/media/manual.pdf)
[Download the manual](/media/manual.pdf?download)
![Favicon](/favicon.ico)
~~~

~~~html
<img src="/media/logo.png" alt="Logo">
<a href="/media/manual.pdf?download">Download</a>
<iframe src="/media/manual.pdf"></iframe>
<video src="/media/film.mp4" controls></video>
~~~

`/favicon.ico` points to the web directory and is delivered by the web server. For files of your
own site the HTML attribute `download` works as well: `<a href="/media/manual.pdf" download>`.

## Twig

{% verbatim %}
~~~twig
{{ image('/media/logo.png', {alt: 'Logo'}) }}               {# <img alt="Logo" src="/media/logo.png"> #}
{{ image('/logo.png') }}                                    {# <img src="/logo.png" alt=""> #}
{{ embed('/media/manual.pdf', {title: 'Manual'}) }}         {# <iframe title="Manual" src="/media/manual.pdf"> #}
{{ link_file('/media/manual.pdf', 'Open the manual') }}     {# <a href="/media/manual.pdf">Open the manual</a> #}
{{ link_download('manual.pdf', 'Manual', {}, true) }}       {# <a href="/media/manual.pdf?download">Manual</a> (PDF, 2.1 MB) #}
{{ file_url('/media/manual.pdf') }}                         {# /media/manual.pdf #}
{{ file_url('/media/manual.pdf', {download: true}) }}       {# /media/manual.pdf?download #}
{{ imagine('/media/portrait.jpg') }}                        {# <img> of a resized copy, see the Imagine plugin #}
~~~
{% endverbatim %}

How a path is interpreted:

- A path that starts with `/media/` (or `media/`) is served by Herbie from `site/media/`. Every
  other path is relative to the web directory.
- External URLs (`https://...`), anchors (`#top`) and query-only values are returned unchanged.
- `link_download()` always refers to a file in `site/media/`, the prefix `/media/` is optional.
- The option `download` has an effect on media files only, a web server ignores `?download`.
- Special characters in file names are encoded. A `?` or `#` in the path starts the query string or
  fragment.

The Imagine plugin function `imagine()` and the filter `|imagine` accept the public URL
`/media/...` as well. A path without a leading `/media/`, as in `imagine('portrait.jpg')`, is
relative to `site/media/` as before.

See also the [Twig functions](doc/indepth/twig-functions) reference.

## Installation in a subdirectory

Herbie does not change raw Markdown and HTML. The browser resolves `/media/logo.png` from the root
of the domain, which is wrong if the site runs in a subdirectory such as `https://example.com/blog/`
(it requests `/media/logo.png` instead of `/blog/media/logo.png`). The same applies if the option
`components.urlManager.niceUrls` is disabled and Herbie routes contain the script name
(`/index.php/media/logo.png`).

The Twig helpers add the base URL and, for media files, the script name. Use `file_url()` in your
content, it works in Markdown and HTML as well, because page content is processed by Twig first
(this is the default):

{% verbatim %}
~~~markdown
![Logo]({{ file_url('/media/logo.png') }})
[Download the manual]({{ file_url('/media/manual.pdf', {download: true}) }})
~~~

~~~html
<img src="{{ file_url('/media/logo.png') }}" alt="Logo">
~~~
{% endverbatim %}

Inside Markdown link syntax use `file_url()`, not `image()` or `link_file()`. They return HTML and
would break the syntax, see [Twig and Markdown processing order](doc/contents/markdown#twig-and-markdown-processing-order).

**Rule:** write `/media/...` if the site runs in the root of the domain, otherwise use
`file_url('/media/...')`.

### Alternative: base element

If your theme sets `<base href="/blog/">`, paths **without** a leading slash (`![Logo](media/logo.png)`)
work in the root and in a subdirectory. The theme must set the base element, which also changes
pure anchors like `#section`, and it does not work without nice URLs. A leading slash makes the
path absolute again.

## Configuration

The directory and the public URL of the media files are configured in `site/config/main.php`.
These are the defaults:

~~~php
return [
    'paths' => [
        'media' => 'SITE_PATH/media',
    ],
    'urls' => [
        'media' => 'WEB_URL/media',
    ],
];
~~~

The URL must be a path below `WEB_URL`. A different domain, for example a CDN, is not supported.
If a directory `web/media/` exists, the web server delivers files from it before Herbie is asked.

## Former download route

The route `/download/...` of earlier versions redirects permanently (301) to `/media/...?download`.
It is deprecated and will be removed in the next major version. Replace links like
`/download/manual.pdf` with `/media/manual.pdf?download`.
