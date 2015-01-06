# Herbie Adminpanel Plugin

`Adminpanel` ist ein [Herbie](http://github.com/getherbie/herbie) Plugin, das eine einfache Administrationsumgebung 
bereit stellt.

## Installation

Das Plugin installierst du via Composer.

	$ composer require getherbie/plugin-adminpanel

Danach aktivierst du das Plugin in der Konfigurationsdatei.

    plugins:
        enable:
            - adminpanel

Das Plugin muss konfiguriert werden. Die Einstellungen unter `plugins/adminpanel/blueprint.yml` müssen manuell 
in die Konfigurationsdatei eingetragen werden. Füge diese unter `plugins.config.adminpanel` ein:

    plugins:
        config:
            adminpanel:
                show_raw_data: true
                password: "md5encoded"
                layouts:
                	default.html:
                ...

Das Passwort für das Adminpanel muss md5-kodiert werden. Nun kannst du die Einstellungen nach belieben anpassen
oder bei Bedarf erweitern.

Das Adminpanel rufst du auf, indem du in der Adresszeile "/adminpanel" eingibst.
