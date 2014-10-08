# Herbie Widgets Plugin

## Installation

Um das Plugin zu installieren, einfach die ZIP-Version dieses Repositories herunterladen und in das Plugins-Verzeichnis entpacken.

Widgets werden als Unterordner innerhalb von 'pages' angelegt, z.B. ein Zweispalter:

    site/pages 
        |-- index.md
        |-- _zweispalter        # Widget-Unterverzeichnis
        |   ├── .htaccess       # Ggf. den Zugang zu best. Dateien erlauben (*.jpg)
        |   ├── index.md        # Widget-Inhalte
        |   ├── image.jpg       # optionale Dateien zur Verwendung im Subtemplate
        |   └── .layout
        |       └── widget.html # Twig-Subtemplate
        └── 02-seite.md


Im Subtemplate können die Inhalte des Widgets wie gewohnt per {{ content() }} eingebunden werden.
Die twig-Variable {{ abspath }} enthält den aktuellen Pfad zum Widget (um z.B.) Bilder im Subtemplate besser refernzieren zu können. Aktuell wird aber auch der String *./* durch den aktuellen Pfad ersetzt.

Das Widget selber kann dann durch {{ widget('Zweispalter') }} in die Seiten eingebunden werden.





