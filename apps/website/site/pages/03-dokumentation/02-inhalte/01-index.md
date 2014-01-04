---
title: Front-Matter
layout: documentation.html
---

# Front-Matter

Mit Front-Matter startet Herbie so richtig durch. Jede Datei, die einen YAML Front-Matter Block enthält, wird von Herbie als eine spezielle Datei abgearbeitet. Der Front-Matter Block muss das erste in der Datei sein und muss zwischen zwei Linien bestehend aus drei Minuszeichen gültiges YAML enthalten. Hier ist ein einfaches Beispiel:

    ---
    title: Mit der eigenen Website durchstarten
    layout: default.html
    ---

Zwischen den Linien aus drei Minuszeichen kannst du vordefinierte Variablen (siehe Referenz unten) oder auch eigene massgeschneiderte Variablen einsetzen.
Diese Variablen sind dann unterhalb des Front-Matter Blocks der Datei, aber auch in allen Layoutdateien als page-Variable verfügbar. Hier ist ein Beispiel:

    {{ text.raw('{{ page.title }}') }}
    {{ text.raw('{{ page.layout }}') }}


## Vordefinierte Variablen

Es gibt einige vordefinierte globale Variablen, die du im Front-Matter Block einer Seite setzen kannst.

<table class="pure-table pure-table-horizontal" width="100%">
    <thead>
        <tr>
            <th width="35%">Variable</th>
            <th width="65%">Beschreibung</th>
        </tr>
    </thead>
    <tr>
        <td><code>title</code></td>
        <td>Der Titel der Seite.</td>
    </tr>
    <tr>
        <td><code>layout</code></td>
        <td>Definiert das Layout, mit welchem die Seite angezeigt werden soll. Gib das Layout inklusive Dateiendung an. Layout-Dateien müssen im Ordner `site/layouts/` abgelegt sein.</td>
    </tr>
</table>


## Eigene Variablen

Jede eigene Variable im Front-Matter Block, die nicht vordefiniert ist, wird von Herbie in den Layoutdateien zur Verfügung gestellt. Wenn du z.B. eine Variable `bodyClass` definierst, kannst du diese im Layout zum Setzen der Meta Description nutzen.

    <!DOCTYPE HTML>
    <html>
    <head>
        <title>{{ text.raw('{{ page.title }}') }}</title>
    </head>
    <body class="{{ text.raw('{{ page.bodyClass }}') }}">
        ...


<p class="pagination">{{ link('dokumentation/inhalte/seiten-erstellen', 'Seiten erstellen<i class="fa fa-arrow-right"></i>', {class:'pure-button'}) }}</p>