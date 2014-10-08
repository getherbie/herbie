# Herbie Widgets Plugin

## Installation

Um das Plugin zu installieren, einfach die ZIP-Version dieses Repositories herunterladen und in das Plugins-Verzeichnis entpacken.

Widgets werden als Unterordner innerhalb von 'pages' angelegt, z.B. ein Zweispalter:

site/pages
    |-- index.md
    |-- _zweispalter
    |   ├── .htaccess       # Ggf. hier den Zugang zu bestimmten Dateien erlauben (*.jpg)
    |   ├── index.md        # Widget-Inhalte
    |   ├── image.jpg
    |   └── .layout
    |       └── widget.html # Twig-Subtemplate
    └── 02-seite.md


Im Subtemplate können die Inhalte des Widgets wie gewohnt per {{ content() }} eingebunden werden.
Die twig-Variable {{ abspath }} enthält den aktuellen Pfad zum Widget (um z.B.) Bilder im Subtemplate besser refernzieren zu können. Aktuell wird aber auch der String *./* durch den aktuellen Pfad ersetzt.

Das Widget selber kann dann durch {{ widget('Zweispalter') }} in die Seiten eingebunden werden.





