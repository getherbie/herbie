<?php

namespace herbie\sysplugins\adminpanel\controllers;

use herbie\Alias;
use herbie\Config;
use herbie\DataRepositoryInterface;
use herbie\Finder;
use herbie\PagePersistenceInterface;
use herbie\PageRepositoryInterface;
use herbie\sysplugins\adminpanel\components\TinyHtmlMinifier;
use herbie\Translator;
use herbie\TwigRenderer;
use herbie\UrlManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class Controller
{
    protected Alias $alias;
    protected Config $config;
    protected DataRepositoryInterface $dataRepository;
    protected Finder $finder;
    protected PagePersistenceInterface $pagePersistence;
    protected PageRepositoryInterface $pageRepository;
    private ResponseFactoryInterface $responseFactory;
    protected ServerRequestInterface $request;
    protected Translator $translator;
    protected TwigRenderer $twig;
    protected UrlManager $urlManager;

    public $controller;
    public $action;

    public function __construct(
        Alias $alias,
        Config $config,
        DataRepositoryInterface $dataRepository,
        Finder $finder,
        PagePersistenceInterface $pagePersistence,
        PageRepositoryInterface $pageRepository,
        ResponseFactoryInterface $responseFactory,
        ServerRequestInterface $request,
        Translator $translator,
        TwigRenderer $twig,
        UrlManager $urlManager
    ) {
        $this->alias = $alias;
        $this->config = $config;
        $this->dataRepository = $dataRepository;
        $this->finder = $finder;
        $this->pagePersistence = $pagePersistence;
        $this->pageRepository = $pageRepository;
        $this->responseFactory = $responseFactory;
        $this->request = $request;
        $this->translator = $translator;
        $this->twig = $twig;
        $this->urlManager = $urlManager;
        $this->init();
    }

    protected function init(): void
    {}
    
    protected function render($template, array $params = [], int $status = 200): ResponseInterface|string
    {
        $content = $this->twig->renderTemplate(
            '@sysplugin/adminpanel/views/' . $template,
            $params
        );
        $content = (new TinyHtmlMinifier([
            'collapse_whitespace' => false,
            'disable_comments' => false,
        ]))->minify($content);
        return $this->createHtmlResponse($content, $status);
    }

    private function minify($code) {
        $search = array(

            // Remove whitespaces after tags
            '/\>[^\S ]+/s',

            // Remove whitespaces before tags
            '/[^\S ]+\</s',

            // Remove multiple whitespace sequences
            '/(\s)+/s',

            // Removes comments
            '/<!--(.|\s)*?-->/'
        );
        $replace = array('>', '<', '\\1');
        $code = preg_replace($search, $replace, $code);
        return $code;
    }

    protected function sendErrorHeader($message, $exit = true, $code = 418)
    {
        header("HTTP/1.1 $code $message");
        header('Content-type: text/plain; charset=utf-8');
        echo $message;
        if ($exit) {
            exit;
        }
    }

    protected function t($message, array $params = [])
    {
        return $this->translator->translate('adminpanel', $message, $params);
    }

    protected function getService($name)
    {
        return \Herbie\Application::getService($name);
    }

    protected function redirect(string $action, int $code = 302): ResponseInterface
    {
        $url = $this->url($action);
        return $this->responseFactory
            ->createResponse()
            ->withHeader('Location', $url)
            ->withStatus($code);
    }

    protected function error(string $message, int $code): ResponseInterface
    {
        return $this->createHtmlResponse($message, $code);
    }

    protected function url(string $action): string
    {
        return '/index.php/admin?action=' . $action;
    }
    
    private function createHtmlResponse(string $content, int $status = 200): ResponseInterface
    {
        $reasonPhrase = $this->getReasonPhrase($status);
        $response = $this->responseFactory->createResponse($status, $reasonPhrase);
        $body = $response->getBody();
        $body->rewind();
        $body->write($content);
        return $response
            ->withHeader('Content-type', 'text/html; charset=utf-8')
            ->withBody($body);        
    }
    
    private function getReasonPhrase(int $status): string
    {
        return match ($status) {
            200 => 'OK',
            302 => 'Found',
            422 => 'Unprocessable Entity',
            default => '',
        };
    }
}
