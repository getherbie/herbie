<?php

declare(strict_types=1);

namespace herbie\sysplugins\simplecontact;

use herbie\Config;
use herbie\Plugin;
use herbie\Translator;
use herbie\TwigRenderer;
use herbie\UrlManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\TwigFunction;

final class SimplecontactSysPlugin extends Plugin
{
    private Config $config;
    private ServerRequestInterface $request;
    private ResponseFactoryInterface $responseFactory;
    private Translator $translator;
    private TwigRenderer $twigRenderer;
    private UrlManager $urlManager;

    public function __construct(
        Config $config,
        ServerRequestInterface $request,
        ResponseFactoryInterface $responseFactory,
        Translator $translator,
        TwigRenderer $twigRenderer,
        UrlManager $urlManager,
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
        $this->translator = $translator;
        $this->twigRenderer = $twigRenderer;
        $this->urlManager = $urlManager;
    }

    public function twigFunctions(): array
    {
        return [
            new TwigFunction('simplecontact', [$this, 'simplecontact'], ['is_safe' => ['html']]),
        ];
    }

    public function simplecontact(): string
    {
        $status = $this->getQueryParam('status');
        [$route] = $this->urlManager->parseRequest();

        $formData = [];
        $formErrors = [];

        if ($this->formSubmitted()) {
            $formData = $this->filterFormData($this->getFormData());
            $formErrors = $this->validateFormData($formData);
            if (empty($formErrors)) {
                $recipient = $this->config->getAsString('plugins.simplecontact.config.recipient'); // validate email
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

        $template = $this->config->get('plugins.simplecontact.config.template', function () {
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
        extract($data); // name, email, message, antispam

        $errors = [];
        if (empty($name)) {
            $errors['name'] = $this->translator->t('simplecontact', 'errorEmptyField');
        }
        if (empty($email)) {
            $errors['email'] = $this->translator->t('simplecontact', 'errorEmptyField');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = $this->translator->t('simplecontact', 'errorInvalidEmail');
        }
        if (empty($message)) {
            $errors['message'] = $this->translator->t('simplecontact', 'errorEmptyField');
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

    /**
     * @return bool
     */
    private function sendEmail(array $data, string $recipient): bool
    {
        // Antispam
        if (!empty($data['antispam'])) {
            return true;
        }

        $subject    = $this->translator->t('simplecontact', 'mailSubject');

        $message = "{$data['message']}\n\n";
        $message .= "Name: {$data['name']}\n";
        $message .= "Email: {$data['email']}\n\n";

        $headers = "From: {$data['name']} <{$data['email']}>";

        return mail($recipient, $subject, $message, $headers); // TODO replace with an smtp mailer
    }

    private function createRedirectResponse(string $route, string $status): ResponseInterface
    {
        $url = $this->urlManager->createAbsoluteUrl($route) . '?status=' . $status;
        return $this->responseFactory->createResponse()->withHeader('Location', $url);
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
        return '@sysplugin/simplecontact/templates/' . $name;
    }
}
