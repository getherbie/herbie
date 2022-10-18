---
title: Daten-Dateien
layout: doc
---

# Daten-Dateien

Zusätzlich zu den eingebauten Variablen von Herbie CMS können eigene Daten definiert werden, auf die man über die Twig Template Engine zugreifen kann. 
Diese Daten werden als JSON- oder YAML-Dateien im Verzeichnis `site/data` abgespeichert.

Mit diesem nützlichen Feature verhindert man unnötige Wiederholungen und macht Datenstrukturen global verfügbar. 
Gleichzeitig hat man damit Zugriff auf Daten, ohne die zentrale Konfigurationsdatei zu verändern.


## Der Daten-Ordner

Im Daten-Ordner erstellt man eine oder mehrere JSON- oder YAML-Dateien, die beliebig strukturierte Daten enthalten können. 
Auf diese Daten hat man im Template über `site.data.<DATEINAME>` Zugriff.


### Beispiel: Eine Liste von Personen

Hier ist ein einfaches Beispiel, wie man Daten-Dateien einsetzen kann.
Damit kann man Copy-Paste-Aktionen in den Twig-Templates verhindern:

In `site/data/persons.yml` werden die Daten erfasst:

    - name: Herbie Hancock
      instrument: Piano
    - name: Jaco Pastorius
      instrument: E-Bass
    - name: Joni Mitchell
      instrument: Guitar, Voice
    - name: Wayne Shorter
      instrument: Saxophone

Auf diese Daten greift man über `site.data.persons` zu. 
Der Dateiname `persons.yml` wird also zum entsprechenden Variablennamen `persons`.

In einem Template gibt man dann die Liste von Personen wie folgt aus:

{% verbatim %}
    {% for person in site.data.persons %}
      <p>Name: {{person.name}}<br>
         Instrument: {{person.name}}</p>
    {% endfor %}
{% endverbatim %}
