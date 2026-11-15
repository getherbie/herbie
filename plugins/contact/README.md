# Herbie Contact Plugin

Contact is a [Herbie](https://github.com/getherbie) plugin that allows you to add a simple contact form to your website.
Such a form consists of the fields name, email and message.

## Installation

The contact plugin is part of Herbie and needs no separate installation.
When enabled, Herbie checks the plugin's Composer requirements and reports if a required package is missing.

~~~php
return [
    // a comma-separated list of plugins to be activated
    'enabledPlugins' => 'contact',
];
~~~

The plugin requires `phpmailer/phpmailer` (`^6.9`).

## Configuration

Under `plugins.contact` the following options are available.

The email address to which the form will be sent:

    recipient: (string)

Aliased template path to custom form template:

    template: (string)

SMTP connection settings are configured under `plugins.contact.smtp`:

    smtp:
        host: (string) SMTP server hostname
        port: (int) SMTP server port, typically 587 for STARTTLS or 465 for SMTPS
        auth: (bool) whether SMTP authentication is enabled
        username: (string) SMTP username
        password: (string) SMTP password
        encryption: (string) `tls`, `ssl`, or an empty string for no encryption

The default settings are intended as placeholders. Configure them for your mail provider, and keep credentials out of version control where possible.

## Notes

If you have enabled page caching, you must disable it.

    ---
    title: Contact form
    cached: false
    ---

Also, the global config `components.pageRendererMiddleware.cache` must be set to false.
Otherwise the page would be loaded from the page cache.

The form is then rendered using the Twig function of the same name.

    {{ contact() }}

Additional content can be placed before or after the function call.
A complete contact page looks like this:

    ---
    title: Contact form
    cached: false
    ---

    # Contact form

    Please fill in all fields of the contact form:

    {{ contact() }}

    We can also be reached via email or phone at...

## Demo

A live demo can be viewed at <https://herbie.tebe.ch/contact>.
