---
title: System Info
layout: doc
---

# System Info

Herbie provides a useful system information function that is helpful during development or troubleshooting. 
It outputs a large amount of information about the current state and environment of the system. 
For example, the current setup displays the following information.

{{ h_info()|replace({'<h1 class="herbie-info-h1">Herbie Info</h1>': ''})|raw }}
