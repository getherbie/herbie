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

The plugin requires `phpmailer/phpmailer` (`^6.9`) and `altcha-org/altcha` (`^2.4`).

## Configuration

Under `plugins.contact` the following options are available.

The email address to which the form will be sent:

    recipient: (string)

Aliased template path to custom form template:

    template: (string)

Names of the hidden honeypot fields that must stay empty. The built-in form renders every configured field dynamically. Names must start with a letter and contain only letters, digits, `_` or `-`; empty, invalid or reserved values fall back to `phone` and `website`:

    honeypots: (array of strings), default ['phone', 'website']

Sender copy settings under `plugins.contact.copy`. Disabled by default; when enabled, the form shows an opt-in checkbox and sends a separate confirmation mail with a copy of the message to the entered address:

    copy:
        enabled: (bool) show the checkbox and send the copy, default false
        defaultChecked: (bool) initial state of the checkbox, default false

The copy is only sent after the original mail was delivered. If the copy fails, the submission is still reported as successful and the error is logged. Its subject and intro line are translated via the `mailSubjectCopy` and `mailCopyIntro` message keys.

CSRF settings under `plugins.contact.csrf`. The secret is required; rendering the form fails if it is empty or still a placeholder like <plugin_contact_csrf_secret>. The token is signed and bound to a signed, per-browser cookie (`herbie_contact_csrf`), so no server session is needed:

    csrf:
        secret: (string)
        tokenTtlSeconds: (int) token and cookie lifetime, default 900 (60..3600)

The cookie is `HttpOnly`, `SameSite=Lax`, scoped to `/`, and marked `Secure` on HTTPS requests. It carries a signed issued-at timestamp that matches the hidden token's timestamp when the form is rendered, and the server checks both against the same TTL. Rotating `csrf.secret` invalidates outstanding cookies and tokens immediately. A submission is accepted only when the form token and the cookie carry the same signed nonce. The `csrf_token()` Twig function is deprecated since 3.3.0 and will be removed in 4.0; `contact_form()` passes the token to its template as the `csrf_token` variable, so custom templates do not need the function.

ALTCHA proof-of-work settings under `plugins.contact.captcha`. The secret is required; rendering the form fails if it is empty or still a placeholder like <plugin_contact_captcha_secret>. Values outside the given range are clamped:

    captcha:
        secret: (string)
        cost: (int) PBKDF2 iterations, default 5000 (1000..100000)
        ttlSeconds: (int) challenge lifetime, default 900 (60..3600)

`cost` controls the proof-of-work difficulty and only affects newly created challenges; already issued challenges stay valid because their cost is stored in the signed payload. The default `ttlSeconds` matches the CSRF token and form age limits (15 min), so a form does not expire earlier than the surrounding checks.

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

The plugin applies five lightweight checks, configured with the options above:

- **CSRF:** Each form render creates a signed token tied to a signed per-browser cookie. The server rejects submissions with a missing, expired, altered or mismatched token/cookie pair and does not send an email.
- **ALTCHA proof-of-work:** Each form render embeds a signed challenge. The bundled widget computes the solution in the browser (the default widget needs one checkbox interaction) and submits it in the hidden `altcha` field. The server rejects submissions with a missing, invalid, or expired solution before any email is sent. The secret must match between challenge creation and verification.
- **Honeypots:** The hidden fields listed in `honeypots` (`phone` and `website` by default) must stay empty. The built-in form renders exactly these fields. If a bot fills one of them, the form pretends success and no email is sent.
- **Form age:** The hidden `form_time` field records when the form was rendered. Submissions faster than `form.minAgeSeconds` or older than `form.maxAgeSeconds` are silently rejected (3 s and 15 min by default). The CSRF token has its own `csrf.tokenTtlSeconds` (15 min by default) and rejects future timestamps.
- **Rate limit:** At most `rateLimit.maxRequests` submissions per `rateLimit.windowSeconds` and IP address (5 per hour by default). This requires a PSR-16 `CacheInterface` implementation; without a cache, no limit is applied. The IP is read from `REMOTE_ADDR`, so a reverse proxy must set it correctly.

Custom templates must keep the hidden fields `csrf_token`, `form_time`, the `altcha` field created by the widget, and every field listed in `honeypots`, otherwise these checks cannot work. Field names used for real input (`name`, `email`, `message`, `form_time`, `csrf_token`, `altcha`, `button`, `send_copy`) cannot be used as honeypots.

The widget script `assets/altcha.min.js` is hosted locally; the contact page loads no third-party scripts and makes no external requests. Proof-of-work requires JavaScript to be enabled in the browser. Because the challenge and solution are bound to the signing secret, keep `captcha.secret` stable for the lifetime of an open form; rotating it invalidates outstanding challenges.

## Notes

The contact form must not be served from cache, because each render creates dynamic security tokens (CSRF, ALTCHA and `form_time`).

Set `cached: false` on the contact page:

    ---
    title: Contact form
    cached: false
    ---

The page renderer only caches pages whose `cached` property is `true`, so it is not necessary to disable `components.pageRendererMiddleware.cache` globally if the contact page is marked as `cached: false`.

The form is rendered with the `contact_form()` Twig function. The old `contact()` name is deprecated since 3.3.0 and will be removed in 4.0.

    {{ contact_form() }}

Additional content can be placed before or after the function call.
A complete contact page looks like this:

    ---
    title: Contact form
    cached: false
    ---

    # Contact form

    Please fill in all fields of the contact form:

    {{ contact_form() }}

    We can also be reached via email or phone at...

## Demo

A live demo can be viewed at <https://herbie.tebe.ch/contact>.
```