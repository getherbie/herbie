# Herbie Highlight Plugin

`Highlight` ist ein [Herbie](http://github.com/getherbie/herbie) Plugin, das den leistungsfähgien Code Syntaxhighlighter [GeSHi](http://qbnz.com/highlighter/) in deine Website einbindet.

## Installation

Das Plugin installierst du via Composer.

	$ composer require getherbie/plugin-highlight

Danach aktivierst du das Plugin in der Konfigurationsdatei.

    plugins:
        enable:
            - highlight

Das Plugin stellt den Tag *{% code %}* zur Verfügung, den du in Seiten nutzen kannst.

## Beispiele

    {% code javascript %}
    var i=10;
    for ( i = 1; i < foo; i++ )
    {
      alert i;
    }
    {% endcode %}

    {% code php %}
    $foo = 45;
    for ( $i = 1; $i < $foo; $i++ )
    {
      echo "$foo<br>";
      --$foo;
    };
    {% endcode %}

    {% code python %}
    def main():
        print "Hallo Welt!"

    if __name__ == '__main__':
        main()
    {% endcode %}
