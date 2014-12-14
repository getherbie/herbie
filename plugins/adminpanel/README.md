# Herbie Adminpanel Plugin

`Adminpanel` ist ein [Herbie](http://github.com/getherbie/herbie) Plugin, das eine einfache Administrationsumgebung 
bereit stellt.

## Installation

Das Plugin installierst du via Composer.

	$ composer require getherbie/plugin-adminpanel

Danach aktivierst du das Plugin in der Konfigurationsdatei.

    plugins:
        adminpanel:
            password: "md5 encoded password"

Das Adminpanel rufst du auf, indem du in der Adresszeile "/adminpanel" eingibst.
