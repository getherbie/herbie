---
title: Features / Vars
menu: Vars
format: md
twig: 1
date: 2019-01-04
keep_extension: 0
content_type: text/html
authors: [Shakespeare, Dickens, Wilde]
categories: [Crime, Arts, Literature]
tags: [Biography, Crime, Arts]
nocache: 1
hidden: 0
custom1: My custom var 1
custom2: My custom var 2
excerpt: An overview about global, site and page vars.
---

# Variables

[page.excerpt]

## Global Variables

| Twig Variable | Output |
|:-------------- |:-------|
| {{ '{{ route }}' }} | {{ route }} |  
| {{ '{{ theme }}' }} | {{ theme }} |  
| {{ '{{ baseUrl }}' }} | {{ baseUrl }} |  


## Site Variables

| Twig Variable | Output |
|:-------------- |:-------|
| {{ '{{ site.charset }}' }} | {{ site.charset }} |  
| {{ '{{ site.language }}' }} | {{ site.language }} |  
| {{ '{{ site.locale }}' }} | {{ site.locale }} |  
| {{ '{{ site.time }}' }} | {{ site.time }} | 
| {{ '{{ site.modified }}' }} | {{ site.modified }} |  


## Page Vars

### As Shortcodes

| Shortcode     | Output |
|:------------- |:-------|
| [[page.title]] | [page.title] |
| [[page.layout]] | [page.layout] |  
| [[page.format]] | [page.format] |
| [[page.date]] | [page.date] |
| [[page.keep_extension]] | [page.keep_extension] |  
| [[page.content_type]] | [page.content_type] |
| [[page.authors]] | [page.authors join=", "] | 
| [[page.categories]] | [page.categories join=", "] |  
| [[page.tags]] | [page.tags join=", "] |
| [[page.path]] | [page.path] |
| [[page.route]] | [page.route] |  
| [[page.nocache]] | [page.nocache] |  
| [[page.hidden]] | [page.hidden] |
| [[page.custom1]] | [page.custom1] |  
| [[page.custom2]] | [page.custom2] |


### As Twig Variables

Twig vars are normally used in layout templates.
But the can also be used in page content.

| Twig Variable | Output |
|:-------------- |:-------|
| {{ '{{ page.title }}' }} | {{ page.title }} |  
| {{ '{{ page.layout }}' }} | {{ page.layout }} |  
| {{ '{{ page.format }}' }} | {{ page.format }} |  
| {{ '{{ page.date }}' }} | {{ page.date }} |  
| {{ '{{ page.keep_extension }}' }} | {{ page.keep_extension }} |  
| {{ '{{ page.content_type }}' }} | {{ page.content_type }} |  
| {{ '{{ page.authors }}' }} | {{ page.authors|join(', ') }} |  
| {{ '{{ page.categories }}' }} | {{ page.categories|join(', ') }} |  
| {{ '{{ page.tags }}' }} | {{ page.tags|join(', ') }} |  
| {{ '{{ page.path }}' }} | {{ page.path }} |  
| {{ '{{ page.route }}' }} | {{ page.route }} |  
| {{ '{{ page.nocache }}' }} | {{ page.nocache }} |  
| {{ '{{ page.hidden }}' }} | {{ page.hidden }} |  
| {{ '{{ page.custom1 }}' }} | {{ page.custom1 }} |  
| {{ '{{ page.custom2 }}' }} | {{ page.custom2 }} |  


<style>
table {
    width: 100%;
}
th {
    padding: 5px 5px;
}
td {
    border-top: 1px solid #ccc;
    padding: 5px 5px;
}
td:first-child {
    width: 40%;
    white-space: nowrap;
}
</style>
