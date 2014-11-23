# Herbie Shortcode Plugin

`Shortcode` ist ein [Herbie](http://github.com/getherbie/herbie) Plugin, mit dem du eigene BB-Codes oder Shortcodes definieren und ausgeben kannst.

## Installation

Das Plugin installierst du am einfachsten via Composer.

	$ composer require getherbie/plugin-shortcode

Danach aktivierst du das Plugin in der Konfigurationsdatei.

    plugins:
        shortcode:

Die eigentlichen Shortcodes definierst du entweder in der PHP-Konfigurationsdatei
oder in der index-Bootstrapdatei.

Technisch gesehen handelt es sich bei den Shortcodes um anonyme PHP-Funktionen.
Dies ist auch der Grund, weshalb Shortcodes nicht einer YAML-Konfigurationsdatei
definiert werden können.

## PHP-Konfigurationsdatei

Shortocdes solltest du bevorzugt in der PHP-Konfigurationsdatei definieren. Eine
einfache Konfiguration sieht wie folgt aus.

    <?php
    return array(
        ...
        'shortcodes' => array(
            'li' => function($atts, $content) { return '<li>' . $content . '</li>'; }),
            'ul' => function($atts, $content) { return '<ul>' . $this['shortcode']->parse($content) . '</ul>'; }),
        )
        ...
    );

## Index-Bootstrapdatei

Alternativ kannst du deine Shortcodes auch in der Bootstrapdatei definieren. Das
sieht dann so aus:

    <?php
    $app = new Herbie\Application('../site');
    $app['shortcode']->add('li', function($atts, $content) { return '<li>' . $content . '</li>'; });
    $app['shortcode']->add('ul', function($atts, $content) use ($app) { return '<ul>' . $app['shortcode']->parse($content) . '</ul>'; }),
    $app->run();

## Anwenden

Die definierten Shortcodes kannst du nun in Seiten und Layouts nutzen. Der
folgende Code gibt eine einfache HTML-Liste aus.

    [ul]
        [li]Herbie[/li]
        [li]Miles[/li]
        [li]Wayne[/li]
    [/ul]

Du kannst deinen Shortcodes beliebige Parameter übergeben. Hier sind einige
Beispiele, wie das aussehen könnte:

    [image file="herbie.png"]
    [gallery folder="media"]
    [caption]Das ist meine Bildlegende[/caption]
    [block size="10"]Ein Inhaltsblock in bestimmter Grösse[/block]
    [video type="youtube" size="medium" /]

Weitere grundlegende Informationen zu Shortcodes findest du in der Wordpress
Shortcode API unter <http://codex.wordpress.org/Shortcode_API>.
