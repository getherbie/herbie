<?php

declare(strict_types=1);

namespace herbie\plugins\contact;

use herbie\AbstractPlugin;
use herbie\Config;
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

#[Plugin('contact', requires: ['phpmailer/phpmailer' => '^6.9'])]
final class ContactPlugin extends AbstractPlugin
{
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

    /**
     * A stateless CSRF token signed with the configured secret.
     */
    public function csrfToken(): string
    {
        if ($this->csrfToken !== null) {
            return $this->csrfToken;
        }

        $timestamp = time();
        $signature = hash_hmac('sha256', (string)$timestamp, $this->getCsrfSecret());
        $this->csrfToken = $timestamp . ':' . $signature;
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

            $formData = $this->filterFormData($this->getFormData());

            // Honeypot fields: silently pretend success for bots.
            if (!empty($formData['antispam']) || !empty($formData['phone']) || !empty($formData['website'])) {
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

        $parts = explode(':', $token, 2);
        if (count($parts) !== 2 || !ctype_digit($parts[0])) {
            return false;
        }

        $timestamp = (int)$parts[0];
        $signature = $parts[1];
        $now = time();
        if ($timestamp < ($now - 3600) || $timestamp > ($now + 60)) {
            return false;
        }

        $expected = hash_hmac('sha256', (string)$timestamp, $this->getCsrfSecret());
        return hash_equals($expected, $signature);
    }

    private function getCsrfSecret(): string
    {
        try {
            $secret = $this->config->getAsString('plugins.contact.secret', '');
        } catch (Throwable) {
            $secret = '';
        }
        // Ignore unreplaced placeholders from config templates.
        if ($secret !== '' && str_starts_with($secret, '<') && str_ends_with($secret, '>')) {
            $secret = '';
        }

        if ($secret === '') {
            throw new \RuntimeException('A non-empty plugins.contact.secret must be configured.');
        }

        return $secret;
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
     * @return array
     */
    private function filterFormData(array $input): array
    {
        $defaults = [
            'name' => '',
            'email' => '',
            'message' => '',
            'antispam' => '',
            'phone' => '',
            'website' => '',
            'form_time' => '',
        ];
        $filtered = $defaults;
        foreach (['name', 'message', 'antispam', 'phone', 'website'] as $field) {
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
        return $filtered;
    }

    private function sendEmail(array $data, string $recipient): bool
    {
        if (!empty($data['antispam']) || !empty($data['phone']) || !empty($data['website'])) {
            return true;
        }

        if (!$this->isValidMailAddress($recipient) || !$this->isValidMailAddress($data['email'])) {
            return false;
        }

        $message = "{$data['message']}\n\n";
        $message .= "Name: {$data['name']}\n";
        $message .= "Email: {$data['email']}\n\n";

        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->isSMTP();
            $mail->Host = $this->config->getAsString('plugins.contact.smtp.host', 'localhost');
            $mail->Port = $this->config->getAsInt('plugins.contact.smtp.port', 587);
            $mail->SMTPAuth = $this->config->getAsBool('plugins.contact.smtp.auth', false);
            $mail->Username = $this->config->getAsString('plugins.contact.smtp.username', '');
            $mail->Password = $this->config->getAsString('plugins.contact.smtp.password', '');
            $mail->SMTPSecure = $this->config->getAsString('plugins.contact.smtp.encryption', 'tls');

            $mail->setFrom($recipient);
            $mail->addAddress($recipient);
            $mail->addReplyTo($data['email'], $data['name']);

            $mail->Subject = $this->translator->t('contact', 'mailSubject');
            $mail->Body = $message;
            $mail->AltBody = $message;

            return $mail->send();
        } catch (Throwable $exception) {
            $this->logger->error('Failed to send contact form email.', ['exception' => $exception]);
            return false;
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

        try {
            $count = (int)$this->cache->get($key, 0);
            if ($count >= 5) {
                return false;
            }
            $this->cache->set($key, $count + 1, 3600);
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
        if ($elapsed < 3) {
            return false;
        }
        if ($elapsed > 3600) {
            return false;
        }
        return true;
    }

    private function getTemplatePath(string $name): string
    {
        return '@plugin/contact/templates/' . $name;
    }
}
