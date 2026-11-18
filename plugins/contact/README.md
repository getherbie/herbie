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

CSRF settings under `plugins.contact.csrf`. The secret is required; rendering the form fails if it is empty or still a placeholder like <plugin_contact_csrf_secret>:

    csrf:
        secret: (string)
        tokenTtlSeconds: (int) token lifetime, default 900 (60..3600)

Form and rate limit settings (values outside the given range are clamped):

    form:
        minAgeSeconds: (int) minimum form age, default 3 (0..60)
        maxAgeSeconds: (int) maximum form age, default 900 (60..3600)
    rateLimit:
        maxRequests: (int) submissions per IP and window, default 5 (1..100)
        windowSeconds: (int) rate limit window, default 3600 (60..86400)

SMTP connection settings are configured under `plugins.contact.smtp`:

    smtp:
        host: (string) SMTP server hostname
        port: (int) SMTP server port, typically 587 for STARTTLS or 465 for SMTPS
        auth: (bool) whether SMTP authentication is enabled
        username: (string) SMTP username
        password: (string) SMTP password
        encryption: (string) `tls`, `ssl`, or an empty string for no encryption

SMTP defaults (`localhost`, 587, TLS) are only fallbacks and do not send mail on their own. Configure `smtp` for your provider and keep credentials out of version control.

## Spam protection

The plugin applies three lightweight checks, configured with the options above:

- **Honeypots:** The hidden fields `phone` and `website` (plus the legacy `antispam`) must stay empty. If a bot fills one of them, the form pretends success and no email is sent.
- **Form age:** The hidden `form_time` field records when the form was rendered. Submissions faster than `form.minAgeSeconds` or older than `form.maxAgeSeconds` are silently rejected (3 s and 15 min by default). The CSRF token has its own `csrf.tokenTtlSeconds` (15 min by default) and rejects future timestamps.
- **Rate limit:** At most `rateLimit.maxRequests` submissions per `rateLimit.windowSeconds` and IP address (5 per hour by default). This requires a PSR-16 `CacheInterface` implementation; without a cache, no limit is applied. The IP is read from `REMOTE_ADDR`, so a reverse proxy must set it correctly.

Custom templates must keep the hidden fields `csrf_token`, `form_time`, `phone` and `website`, otherwise these checks cannot work.

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
```