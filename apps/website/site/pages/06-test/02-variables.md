---
title: Test mit Variablen
---

# Test mit Variablen

{% for key, value in page %}
 - {{ key }}: {{ value }}
{% endfor %}

Site:

{% for key, value in site %}
 - {{ key }}: {{ value }}
{% endfor %}

