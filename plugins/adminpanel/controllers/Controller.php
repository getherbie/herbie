<?php

namespace herbie\plugin\adminpanel\controllers;

class Controller
{
    protected $app;
    protected $request;
    protected $session;
    public $controller;
    public $action;

    public function __construct($app, $session)
    {
        $this->app = $app;
        $this->session = $session;
        $this->request = $app['request'];
    }

    protected function render($template, array $params = [])
    {
        return $this->app['twig']->render(
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

}