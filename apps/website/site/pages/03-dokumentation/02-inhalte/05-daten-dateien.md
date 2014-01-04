---
title: Daten-Dateien
layout: documentation.html
---

# Daten-Dateien

Zusätzlich zu den eingebauten Variablen von Herbie kannst du deine eigenen Daten definieren, auf die du dann über das Twig Templating System zugreifen kannst. Diese Daten werden als YAML-Dateien im Verzeichnis `site/data` abgespeichert.

Mit diesem nützlichen Feature kannst du unnötige Weiderholungen in den Template-Dateien verhindern und auf spezielle Optionen zugreifen, ohne die globale Konfigurationsdatei anzupassen.


## Der Daten-Ordner

Im Daten-Ordner kannst du eine oder mehrere YAML-Dateien erstellen, die beliebig aufgebaute Daten enthalten können. Auf diese Daten kannst du im Template über `site.data` zugreifen.


### Beispiel: Liste von Personen

Hier ist ein einfaches Beispiel, wie du Daten-Dateien einsetzen kannst, um Copy-Paste-Aktionen in deinen Twig-Templates zu verhindern:

In data/persons.yml erfasst du die Daten:

    - name: Herbie Hancock
      instrument: Piano

    - name: Jaco Pastorius
      instrument: E-Bass

    - name: Joni Mitchell
      instrument: Guitar, Voice

Auf diese Daten kannst du über site.data.musicians zugreifen. Beachte, dass der Dateiname musicians.yml zum Variablennamen wird.

In einem Template oder auch Textdatei gibst du die Liste von Personen wie folgt aus:

    {{ text.raw('{% for person in site.data.persons %}
      <p>Name: {{person.name}}<br>
         Instrument: {{person.name}}</p>
    {% endfor %}')|raw }}


<p class="pagination">{{ link('dokumentation/anpassung/index', 'Templates<i class="fa fa-arrow-right"></i>', {class:'pure-button'}) }}</p>