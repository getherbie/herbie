<?php

namespace herbie\sysplugins\adminpanel\controllers;

use herbie\Alias;
use herbie\Config;
use herbie\DataRepositoryInterface;
use herbie\Finder;
use herbie\PagePersistenceInterface;
use herbie\PageRepositoryInterface;
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
    }

    protected function render($template, array $params = []): ResponseInterface|string
    {
        return $this->twig->renderTemplate(
            '@sysplugin/adminpanel/views/' . $template,
            $params
        );
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

    protected function redirect(string $action, int $code = 301): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse($code)
            ->withHeader('Location', $this->url($action));
    }

    protected function error(string $message, int $code): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($code);
        $body = $response->getBody();
        $body->rewind();
        $body->write($message);
        return $response
            ->withHeader('Content-type', 'text/plain; charset=utf-8')
            ->withBody($body);
    }

    protected function url(string $action): string
    {
        return '/admin/?action=' . $action;
    }
}
