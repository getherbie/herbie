---
title: Data Files
layout: doc
---

# Data Files

In addition to the built-in variables of Herbie CMS, custom data can be defined and accessed through the Twig template engine.
This data is stored as JSON or YAML files in the `site/data` directory.

This useful feature prevents unnecessary repetitions and makes data structures globally available.
At the same time, one has access to data without changing the central configuration file.


## The data folder

In the data folder you create one or more JSON or YAML files that can contain any structured data.
You can access this data in the template via `site.data.<FILENAME>`.


### Example: A list of people

Here is a simple example of how to use data files.
This can be used to prevent copy-paste actions in Twig templates:

In `site/data/persons.yml` the data is recorded:

    - name: Herbie Hancock
      instrument: Piano
    - name: Jaco Pastorius
      instrument: Electric bass
    - name: Joni Mitchell
      instrument: Guitar, Voice
    - name: Wayne Shorter
      instrument: Saxophone

This data is accessed via `site.data.persons`.
The file name `persons.yml` thus becomes the corresponding variable name `persons`.

In a template you then output the list of persons as follows:

{% verbatim %}
    {% for person in site.data.persons %}
      <p>Name: {{person.name}}<br>
         Instrument: {{person.name}}</p>
    {% endfor %}
{% endverbatim %}
