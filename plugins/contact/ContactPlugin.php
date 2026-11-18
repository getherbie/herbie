<?php

declare(strict_types=1);

namespace herbie\plugins\contact;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\VerifySolutionOptions;
use herbie\AbstractPlugin;
use herbie\Config;
use herbie\events\ResponseGeneratedEvent;
use herbie\Page;
use herbie\PageRepositoryInterface;
use herbie\Plugin;
use herbie\Translator;
use herbie\TwigRenderer;
use herbie\UrlManager;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Twig\TwigFunction;

#[Plugin('contact', requires: ['phpmailer/phpmailer' => '^6.9', 'altcha-org/altcha' => '^2.4'])]
final class ContactPlugin extends AbstractPlugin
{
    private const CAPTCHA_COUNTER_MIN = 5000;
    private const CAPTCHA_COUNTER_MAX = 10000;
    private const CSRF_COOKIE_NAME = 'herbie_contact_csrf';
    private const CSRF_NONCE_PATTERN = '/^[a-f0-9]{32}$/';
    private const DEFAULT_CAPTCHA_COST = 5000;
    private const DEFAULT_CAPTCHA_TTL_SECONDS = 900;
    private const DEFAULT_HONEYPOTS = ['phone', 'website'];
    private const DEFAULT_CSRF_TOKEN_TTL_SECONDS = 900;
    private const DEFAULT_FORM_MIN_AGE_SECONDS = 3;
    private const DEFAULT_FORM_MAX_AGE_SECONDS = 900;
    private const DEFAULT_RATE_LIMIT_MAX_REQUESTS = 5;
    private const DEFAULT_RATE_LIMIT_WINDOW_SECONDS = 3600;

    private Config $config;
    private ?CacheInterface $cache = null;
    private PageRepositoryInterface $pageRepository;
    private ServerRequestInterface $request;
    private ResponseFactoryInterface $responseFactory;
    private Translator $translator;
    private TwigRenderer $twigRenderer;
    private UrlManager $urlManager;
    private LoggerInterface $logger;
    private ?string $csrfToken = null;
    private ?string $csrfCookieValue = null;

    public function __construct(
        Config $config,
        PageRepositoryInterface $pageRepository,
        ServerRequestInterface $request,
        ResponseFactoryInterface $responseFactory,
        Translator $translator,
        TwigRenderer $twigRenderer,
        UrlManager $urlManager,
        LoggerInterface $logger,
        ?CacheInterface $cache = null,
    ) {
        $this->config = $config;
        $this->pageRepository = $pageRepository;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
        $this->translator = $translator;
        $this->twigRenderer = $twigRenderer;
        $this->urlManager = $urlManager;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    public function twigFunctions(): array
    {
        return [
            new TwigFunction('contact', [$this, 'contact'], ['is_safe' => ['html']]),
            new TwigFunction('csrf_token', fn(): string => $this->csrfToken()),
        ];
    }

    public function eventListeners(): array
    {
        return [
            [ResponseGeneratedEvent::class, [$this, 'onResponseGenerated']],
        ];
    }

    public function onResponseGenerated(ResponseGeneratedEvent $event): void
    {
        if ($this->csrfCookieValue === null) {
            return;
        }

        $ttl = $this->configClampedInt('csrf.tokenTtlSeconds', self::DEFAULT_CSRF_TOKEN_TTL_SECONDS, 60, 3600);
        $secure = $this->request->getUri()->getScheme() === 'https' ? '; Secure' : '';
        $cookie = sprintf(
            '%s=%s; Path=/; Max-Age=%d; HttpOnly; SameSite=Lax%s',
            self::CSRF_COOKIE_NAME,
            $this->csrfCookieValue,
            $ttl,
            $secure,
        );
        $event->setResponse($event->getResponse()->withAddedHeader('Set-Cookie', $cookie));
    }

    /**
     * A stateless CSRF token bound to a signed per-browser cookie.
     */
    public function csrfToken(): string
    {
        if ($this->csrfToken !== null) {
            return $this->csrfToken;
        }

        $nonce = $this->getCsrfCookieNonce() ?? bin2hex(random_bytes(16));
        $this->csrfCookieValue = $this->signCsrfCookie($nonce);
        $timestamp = (string)time();
        $signature = $this->signCsrfTokenData($timestamp, $nonce);
        $this->csrfToken = $timestamp . ':' . $nonce . ':' . $signature;
        return $this->csrfToken;
    }

    public function contact(): string
    {
        $this->assertContactPageNotCached();

        $status = $this->getQueryParam('status');
        [$route] = $this->urlManager->parseRequest();

        $formData = [];
        $formErrors = [];

        if ($this->formSubmitted()) {
            // Simple rate limiting per IP address.
            if (!$this->checkRateLimit()) {
                $this->logger->warning('Contact form rate limited.');
                $response = $this->createRedirectResponse($route, 'failed');
                $this->emitResponse($response);
                exit;
            }

            // Verify the CSRF token before processing the form.
            if (!$this->isValidCsrfToken($this->getFormData())) {
                $this->logger->warning('Contact form rejected: invalid CSRF token.');
                $response = $this->createRedirectResponse($route, 'failed');
                $this->emitResponse($response);
                exit;
            }

            // Verify the ALTCHA proof-of-work before processing the form.
            if (!$this->isValidAltchaPayload($this->getFormData())) {
                $this->logger->warning('Contact form rejected: invalid CAPTCHA.');
                $response = $this->createRedirectResponse($route, 'failed');
                $this->emitResponse($response);
                exit;
            }

            $formData = $this->filterFormData($this->getFormData());

            // Honeypot fields: silently pretend success for bots.
            if ($this->hasFilledHoneypot($formData)) {
                $this->logger->info('Contact form honeypot triggered.');
                $response = $this->createRedirectResponse($route, 'successful');
                $this->emitResponse($response);
                exit;
            }

            // Time-based check: reject too-fast submissions (bots).
            if (!$this->isHumanTiming($formData)) {
                $this->logger->info('Contact form rejected: too fast submission.');
                $response = $this->createRedirectResponse($route, 'successful');
                $this->emitResponse($response);
                exit;
            }

            $formErrors = $this->validateFormData($formData);
            if (empty($formErrors)) {
                $recipient = $this->config->getAsString('plugins.contact.recipient');
                $status = $this->sendEmail($formData, $recipient);
                if ($status) {
                    $response = $this->createRedirectResponse($route, 'successful');
                } else {
                    $response = $this->createRedirectResponse($route, 'failed');
                }
                $this->emitResponse($response);
                exit;
            }
        }

        if ($this->formReset()) {
            $formData = [];
            $formErrors = [];
        }

        $template = $this->config->get('plugins.contact.template', function () {
            return $this->getTemplatePath('form.twig');
        });

        return $this->twigRenderer->renderTemplate($template, [
            'data' => $formData,
            'errors' => $formErrors,
            'status' => $status,
            'route' => $route,
            'csrf_token' => $this->csrfToken(),
            'form_time' => time(),
            'altcha_challenge' => $this->createAltchaChallenge(),
            'honeypot_fields' => $this->getHoneypotFields(),
            'copy_enabled' => $this->config->getAsBool('plugins.contact.copy.enabled', false),
            'copy_checked' => $formData === []
                ? $this->config->getAsBool('plugins.contact.copy.defaultChecked', false)
                : !empty($formData['send_copy']),
        ]);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function isValidCsrfToken(array $body): bool
    {
        $token = $body['csrf_token'] ?? '';
        if (!is_string($token) || $token === '') {
            return false;
        }

        $parts = explode(':', $token);
        if (count($parts) !== 3) {
            return false;
        }

        [$timestamp, $nonce, $signature] = $parts;
        if (!ctype_digit($timestamp) || preg_match(self::CSRF_NONCE_PATTERN, $nonce) !== 1) {
            return false;
        }

        $issuedAt = (int)$timestamp;
        $now = time();
        $ttl = $this->configClampedInt('csrf.tokenTtlSeconds', self::DEFAULT_CSRF_TOKEN_TTL_SECONDS, 60, 3600);
        if ($issuedAt < ($now - $ttl) || $issuedAt > $now) {
            return false;
        }

        if (!hash_equals($this->signCsrfTokenData($timestamp, $nonce), $signature)) {
            return false;
        }

        $cookieNonce = $this->getCsrfCookieNonce();
        return $cookieNonce !== null && hash_equals($cookieNonce, $nonce);
    }

    private function getCsrfCookieNonce(): ?string
    {
        $cookie = $this->request->getCookieParams()[self::CSRF_COOKIE_NAME] ?? null;
        if (!is_string($cookie)) {
            return null;
        }

        $parts = explode(':', $cookie);
        if (count($parts) !== 2) {
            return null;
        }

        [$nonce, $signature] = $parts;
        if (preg_match(self::CSRF_NONCE_PATTERN, $nonce) !== 1) {
            return null;
        }

        $expected = hash_hmac('sha256', 'cookie:' . $nonce, $this->getCsrfSecret());
        return hash_equals($expected, $signature) ? $nonce : null;
    }

    private function signCsrfCookie(string $nonce): string
    {
        return $nonce . ':' . hash_hmac('sha256', 'cookie:' . $nonce, $this->getCsrfSecret());
    }

    private function signCsrfTokenData(string $timestamp, string $nonce): string
    {
        return hash_hmac('sha256', 'token:' . $timestamp . ':' . $nonce, $this->getCsrfSecret());
    }

    private function getCsrfSecret(): string
    {
        return $this->getRequiredSecret('csrf.secret');
    }

    private function getCaptchaSecret(): string
    {
        return $this->getRequiredSecret('captcha.secret');
    }

    private function getRequiredSecret(string $key): string
    {
        try {
            $secret = $this->config->getAsString('plugins.contact.' . $key, '');
        } catch (Throwable) {
            $secret = '';
        }
        // Ignore unreplaced placeholders from config templates.
        if ($secret !== '' && str_starts_with($secret, '<') && str_ends_with($secret, '>')) {
            $secret = '';
        }

        if ($secret === '') {
            throw new \RuntimeException(sprintf('A non-empty plugins.contact.%s must be configured.', $key));
        }

        return $secret;
    }

    private function createAltchaChallenge(): string
    {
        $secret = $this->getCaptchaSecret();
        $altcha = new Altcha($secret, $secret);
        return $altcha->createChallenge(new CreateChallengeOptions(
            algorithm: new Pbkdf2(),
            cost: $this->configClampedInt('captcha.cost', self::DEFAULT_CAPTCHA_COST, 1000, 100000),
            counter: random_int(self::CAPTCHA_COUNTER_MIN, self::CAPTCHA_COUNTER_MAX),
            expiresAt: time() + $this->configClampedInt('captcha.ttlSeconds', self::DEFAULT_CAPTCHA_TTL_SECONDS, 60, 3600),
        ))->toJson();
    }

    /**
     * @param array<string, mixed> $body
     */
    private function isValidAltchaPayload(array $body): bool
    {
        $payload = $body['altcha'] ?? '';
        if (!is_string($payload) || $payload === '') {
            return false;
        }

        try {
            $secret = $this->getCaptchaSecret();
            $altcha = new Altcha($secret, $secret);
            return $altcha->verifySolution(new VerifySolutionOptions(
                algorithm: new Pbkdf2(),
                payload: $payload,
            ))->verified;
        } catch (Throwable) {
            return false;
        }
    }

    private function configClampedInt(string $key, int $default, int $min, int $max): int
    {
        return max($min, min($max, $this->config->getAsInt('plugins.contact.' . $key, $default)));
    }

    private function getFormData(): array
    {
        return (array)$this->request->getParsedBody();
    }

    private function formSubmitted(): bool
    {
        if (!$this->isPostRequest()) {
            return false;
        }
        $body = $this->getFormData();
        return isset($body['button']) && ($body['button'] === 'submit');
    }

    private function formReset(): bool
    {
        if (!$this->isPostRequest()) {
            return false;
        }
        $body = $this->getFormData();
        return isset($body['button']) && ($body['button'] === 'reset');
    }

    private function isPostRequest(): bool
    {
        return $this->request->getMethod() === 'POST';
    }

    private function getQueryParam(string $name, string $default = ''): string
    {
        $params = $this->request->getQueryParams();
        return (string)($params[$name] ?? $default);
    }

    private function validateFormData(array $data): array
    {
        $errors = [];
        $name = (string)($data['name'] ?? '');
        $email = (string)($data['email'] ?? '');
        $message = (string)($data['message'] ?? '');

        if (trim($name) === '') {
            $errors['name'] = $this->translator->t('contact', 'errorEmptyField');
        }
        if (trim($email) === '') {
            $errors['email'] = $this->translator->t('contact', 'errorEmptyField');
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = $this->translator->t('contact', 'errorInvalidEmail');
        }
        if (trim($message) === '') {
            $errors['message'] = $this->translator->t('contact', 'errorEmptyField');
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function getHoneypotFields(): array
    {
        $reserved = ['name', 'email', 'message', 'form_time', 'csrf_token', 'altcha', 'button', 'send_copy'];
        $normalizedReserved = array_map('strtolower', $reserved);
        $fields = [];
        foreach ($this->config->getAsArray('plugins.contact.honeypots') as $field) {
            if (
                is_string($field)
                && preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $field) === 1
                && !in_array(strtolower($field), $normalizedReserved, true)
            ) {
                $fields[] = $field;
            }
        }
        return $fields === [] ? self::DEFAULT_HONEYPOTS : array_values(array_unique($fields));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hasFilledHoneypot(array $data): bool
    {
        foreach ($this->getHoneypotFields() as $field) {
            if (!empty($data[$field])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    private function filterFormData(array $input): array
    {
        $honeypots = $this->getHoneypotFields();
        $defaults = [
            'name' => '',
            'email' => '',
            'message' => '',
            'form_time' => '',
            'send_copy' => false,
        ];
        foreach ($honeypots as $field) {
            $defaults[$field] = '';
        }
        $filtered = $defaults;
        foreach (array_merge(['name', 'message'], $honeypots) as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $filtered[$field] = strip_tags($input[$field]);
            }
        }
        if (isset($input['email']) && is_string($input['email'])) {
            $filtered['email'] = (string)filter_var($input['email'], FILTER_SANITIZE_EMAIL);
        }
        if (isset($input['form_time']) && is_string($input['form_time'])) {
            $filtered['form_time'] = preg_replace('/[^0-9]/', '', $input['form_time']) ?? '';
        }
        if (isset($input['send_copy']) && is_string($input['send_copy'])) {
            $filtered['send_copy'] = filter_var($input['send_copy'], FILTER_VALIDATE_BOOLEAN);
        }
        return $filtered;
    }

    private function sendEmail(array $data, string $recipient): bool
    {
        if ($this->hasFilledHoneypot($data)) {
            return true;
        }

        if (!$this->isValidMailAddress($recipient) || !$this->isValidMailAddress($data['email'])) {
            return false;
        }

        $message = "{$data['message']}\n\n";
        $message .= "Name: {$data['name']}\n";
        $message .= "Email: {$data['email']}\n\n";

        try {
            $mail = $this->createMailer();
            $mail->setFrom($recipient);
            $mail->addAddress($recipient);
            $mail->addReplyTo($data['email'], $data['name']);

            $mail->Subject = $this->translator->t('contact', 'mailSubject');
            $mail->Body = $message;
            $mail->AltBody = $message;

            $sent = $mail->send();
        } catch (Throwable $exception) {
            $this->logger->error('Failed to send contact form email.', ['exception' => $exception]);
            return false;
        }

        if (!$sent) {
            return false;
        }

        // A failed copy must not turn a delivered message into a failed submission.
        $this->sendCopy($data, $recipient);

        return true;
    }

    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->isSMTP();
        $mail->Host = $this->config->getAsString('plugins.contact.smtp.host', 'localhost');
        $mail->Port = $this->config->getAsInt('plugins.contact.smtp.port', 587);
        $mail->SMTPAuth = $this->config->getAsBool('plugins.contact.smtp.auth', false);
        $mail->Username = $this->config->getAsString('plugins.contact.smtp.username', '');
        $mail->Password = $this->config->getAsString('plugins.contact.smtp.password', '');
        $mail->SMTPSecure = $this->config->getAsString('plugins.contact.smtp.encryption', 'tls');

        return $mail;
    }

    private function shouldSendCopy(array $data): bool
    {
        return $this->config->getAsBool('plugins.contact.copy.enabled', false)
            && !empty($data['send_copy'])
            && $this->isValidMailAddress((string)($data['email'] ?? ''));
    }

    private function sendCopy(array $data, string $recipient): void
    {
        if (!$this->shouldSendCopy($data)) {
            return;
        }

        try {
            $mail = $this->createMailer();
            $mail->setFrom($recipient);
            $mail->addAddress($data['email']);
            $mail->addReplyTo($recipient);

            $body = $this->translator->t('contact', 'mailCopyIntro') . "\n\n" . $data['message'] . "\n";
            $mail->Subject = $this->translator->t('contact', 'mailSubjectCopy');
            $mail->Body = $body;
            $mail->AltBody = $body;

            $mail->send();
        } catch (Throwable $exception) {
            $this->logger->warning('Failed to send contact form copy.', ['exception' => $exception]);
        }
    }

    private function isValidMailAddress(string $address): bool
    {
        return strpbrk($address, "\r\n") === false
            && filter_var($address, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function createRedirectResponse(string $route, string $status): ResponseInterface
    {
        $url = $this->urlManager->createAbsoluteUrl($route) . '?status=' . rawurlencode($status);
        return $this->responseFactory->createResponse(303)->withHeader('Location', $url);
    }

    /**
     * @param ResponseInterface $response
     */
    private function emitResponse(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();
        http_response_code($statusCode);
        foreach ($response->getHeaders() as $k => $values) {
            foreach ($values as $v) {
                header(sprintf('%s: %s', $k, $v), false);
            }
        }
        echo $response->getBody();
    }

    private function assertContactPageNotCached(): void
    {
        [$route] = $this->urlManager->parseRequest();
        $page = $this->pageRepository->findAll()->getItem($route);
        if ($page instanceof Page && $page->getCached()) {
            throw new \RuntimeException(
                'The contact page must be configured with "cached: false" to avoid caching CSRF tokens.',
            );
        }
    }

    private function checkRateLimit(): bool
    {
        if ($this->cache === null) {
            return true;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'contact_rate_' . md5((string)$ip);

        $maxRequests = $this->configClampedInt('rateLimit.maxRequests', self::DEFAULT_RATE_LIMIT_MAX_REQUESTS, 1, 100);
        $window = $this->configClampedInt('rateLimit.windowSeconds', self::DEFAULT_RATE_LIMIT_WINDOW_SECONDS, 60, 86400);

        try {
            $count = (int)$this->cache->get($key, 0);
            if ($count >= $maxRequests) {
                return false;
            }
            $this->cache->set($key, $count + 1, $window);
        } catch (Throwable) {
            return true;
        }

        return true;
    }

    private function isHumanTiming(array $data): bool
    {
        $started = (int)($data['form_time'] ?? 0);
        if ($started <= 0) {
            return false;
        }
        $elapsed = time() - $started;
        $minAge = $this->configClampedInt('form.minAgeSeconds', self::DEFAULT_FORM_MIN_AGE_SECONDS, 0, 60);
        if ($elapsed < $minAge) {
            return false;
        }
        $maxAge = $this->configClampedInt('form.maxAgeSeconds', self::DEFAULT_FORM_MAX_AGE_SECONDS, 60, 3600);
        if ($elapsed > $maxAge) {
            return false;
        }
        return true;
    }

    private function getTemplatePath(string $name): string
    {
        return '@plugin/contact/templates/' . $name;
    }
}
