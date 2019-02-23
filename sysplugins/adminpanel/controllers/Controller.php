<?php

namespace herbie\plugin\adminpanel\controllers;

abstract class Controller
{
    protected $alias;
    protected $config;
    protected $request;
    protected $twig;

    public $controller;
    public $action;

    public function __construct()
    {
        $this->alias = $this->getService('Alias');
        $this->config = $this->getService('Config');
        $this->request = $this->getService('Request');
        $this->twig = $this->getService('Twig');
    }

    protected function render($template, array $params = [])
    {
        return $this->twig->render(
            '@plugin/adminpanel/views/' . $template,
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
        return $this->getService('Translator')->translate('adminpanel', $message, $params);
    }

    protected function getService($name)
    {
        return \Herbie\Application::getService($name);
    }

}
