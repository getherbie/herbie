---
title: Twig Tests
layout: doc
---

# Twig Tests

Herbie is using [Twig Tests](https://twig.symfony.com/doc/3.x/tests/index.html) for evaluating boolean conditions in layout and content files.
In addition to the built-in tests of Twig, the following tests are available.

{{ snippet("@site/snippets/twig_features.twig", {type:"twig_tests"}) }}

## Built-in Tests

In addition to the tests mentioned above, Twig's built-in tests are of course also available.
Please note that some of the tests may require the installation of additional Composer packages.

<ul>
{% for test in site.data.twig_tests_builtin %}
<li><a href="https://twig.symfony.com/doc/3.x/tests/{{ test.slug ?: test.name }}.html" target="_blank">{{ test.name }}</a></li>
{% endfor %}
</ul>
