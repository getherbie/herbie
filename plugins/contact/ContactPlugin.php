<?php

declare(strict_types=1);

namespace herbie\sysplugins\contact;

use herbie\AbstractPlugin;
use herbie\Config;
use herbie\Plugin;
use herbie\Translator;
use herbie\TwigRenderer;
use herbie\UrlManager;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Twig\TwigFunction;

#[Plugin('contact', requires: ['phpmailer/phpmailer' => '^6.9'])]
final class ContactPlugin extends AbstractPlugin
{
    private Config $config;
    private ServerRequestInterface $request;
    private ResponseFactoryInterface $responseFactory;
    private Translator $translator;
    private TwigRenderer $twigRenderer;
    private UrlManager $urlManager;
    private LoggerInterface $logger;

    public function __construct(
        Config $config,
        ServerRequestInterface $request,
        ResponseFactoryInterface $responseFactory,
        Translator $translator,
        TwigRenderer $twigRenderer,
        UrlManager $urlManager,
        LoggerInterface $logger,
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
        $this->translator = $translator;
        $this->twigRenderer = $twigRenderer;
        $this->urlManager = $urlManager;
        $this->logger = $logger;
    }

    public function twigFunctions(): array
    {
        return [
            new TwigFunction('contact', [$this, 'contact'], ['is_safe' => ['html']]),
        ];
    }

    public function contact(): string
    {
        $status = $this->getQueryParam('status');
        [$route] = $this->urlManager->parseRequest();

        $formData = [];
        $formErrors = [];

        if ($this->formSubmitted()) {
            $formData = $this->filterFormData($this->getFormData());
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
        ]);
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
        extract($data);

        $errors = [];
        if (empty($name)) {
            $errors['name'] = $this->translator->t('contact', 'errorEmptyField');
        }
        if (empty($email)) {
            $errors['email'] = $this->translator->t('contact', 'errorEmptyField');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = $this->translator->t('contact', 'errorInvalidEmail');
        }
        if (empty($message)) {
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
        ];
        $definition = [
            'name' => FILTER_SANITIZE_STRING,
            'email' => FILTER_SANITIZE_EMAIL,
            'message' => FILTER_SANITIZE_STRING,
            'antispam' => FILTER_SANITIZE_STRING,
        ];
        $filtered = (array)filter_var_array($input, $definition);
        return array_merge($defaults, $filtered);
    }

    private function sendEmail(array $data, string $recipient): bool
    {
        if (!empty($data['antispam'])) {
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
        $url = $this->urlManager->createAbsoluteUrl($route) . '?status=' . $status;
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

    private function getTemplatePath(string $name): string
    {
        return '@sysplugin/contact/templates/' . $name;
    }
}
