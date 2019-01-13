---
title: Variables
menu: Variables
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
excerpt: An overview about all global, site and page variables.
---

# Variables

{{ page.excerpt }}

## Global Variables

| Variable | Example | Type |
|:-------------- |:-------|:-----|
| {{ '{{ route }}' }} | {{ route }} | string |  
| {{ '{{ theme }}' }} | {{ theme }} | string |  
| {{ '{{ baseUrl }}' }} | {{ baseUrl }} | string |  


## Site Variables

| Variable | Example | Type |
|:-------------- |:-------|:-----|
| {{ '{{ site.charset }}' }} | {{ site.charset }} | string |  
| {{ '{{ site.language }}' }} | {{ site.language }} | string |  
| {{ '{{ site.locale }}' }} | {{ site.locale }} | string |
| {{ '{{ site.time }}' }} | {{ site.time }} | string |
| {{ '{{ site.modified }}' }} | {{ site.modified }} | string |  
| {{ '{{ site.data }}' }} | get all data | array |
| {{ '{{ site.data.&lt;name&gt; }}' }} | get data for "name" | array |  
| {{ '{{ site.menu }}' }} | get menu list | array |
| {{ '{{ site.tree }}' }} | get menu tree | array (tree) |
| {{ '{{ site.rootPath }}' }} | get menu trail | array |  


## Page Variables

<!--

### As Shortcodes

| Shortcode     | Example | Type |
|:------------- |:-------|:------|
| [[page.title]] | [page.title] | string |
| [[page.layout]] | [page.layout] | string |  
| [[page.format]] | [page.format] | string |
| [[page.date]] | [page.date] | string |
| [[page.keep_extension]] | [page.keep_extension] | int |  
| [[page.content_type]] | [page.content_type] | string |
| [[page.authors]] | [page.authors join=", "] | array | 
| [[page.categories]] | [page.categories join=", "] | array |  
| [[page.tags]] | [page.tags join=", "] | array |
| [[page.path]] | [page.path] | string |
| [[page.route]] | [page.route] | string |  
| [[page.nocache]] | [page.nocache] | int |  
| [[page.hidden]] | [page.hidden] | int |
| [[page.custom1]] | [page.custom1] | mixed |  
| [[page.custom2]] | [page.custom2] | mixed |


### As Twig Variables

Twig variables are normally used in layout templates.
But they can also be used in page content.
-->

| Variable | Example | Type |
|:-------------- |:-------|:-----|
| {{ '{{ page.title }}' }} | {{ page.title }} | string |  
| {{ '{{ page.layout }}' }} | {{ page.layout }} | string |  
| {{ '{{ page.format }}' }} | {{ page.format }} | string |  
| {{ '{{ page.date }}' }} | {{ page.date }} | string |  
| {{ '{{ page.keep_extension }}' }} | {{ page.keep_extension }} | int |  
| {{ '{{ page.content_type }}' }} | {{ page.content_type }} | string |  
| {{ '{{ page.authors }}' }} | {{ page.authors|join(', ') }} | array |  
| {{ '{{ page.categories }}' }} | {{ page.categories|join(', ') }} | array |  
| {{ '{{ page.tags }}' }} | {{ page.tags|join(', ') }} | array |  
| {{ '{{ page.path }}' }} | {{ page.path }} | string |  
| {{ '{{ page.route }}' }} | {{ page.route }} | string |  
| {{ '{{ page.nocache }}' }} | {{ page.nocache }} | int |  
| {{ '{{ page.hidden }}' }} | {{ page.hidden }} | int |  
| {{ '{{ page.custom1 }}' }} | {{ page.custom1 }} | mixed |  
| {{ '{{ page.custom2 }}' }} | {{ page.custom2 }} | mixed |  


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
