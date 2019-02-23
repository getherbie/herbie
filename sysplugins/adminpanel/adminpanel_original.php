<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\adminpanel;

use Herbie\DI;
use Herbie\Hook;
use Herbie\Loader\FrontMatterLoader;
use Herbie\Menu;
use Twig_SimpleFunction;


class AdminpanelPlugin
{

    protected $content;

    protected $panel;

    protected $request;

    protected $config;

    public function __construct()
    {
        session_start();
        $this->config = DI::get('Config');
        $this->request = DI::get('Request');
    }

    public function install()
    {
        Hook::attach('pluginsInitialized', [$this, 'pluginsInitialized']);
        Hook::attach('twigInitialized', [$this, 'addTwigFunction']);
        Hook::attach('outputGenerated', [$this, 'outputGenerated']);
    }

    public function addTwigFunction($twig)
    {
        $function = new Twig_SimpleFunction('rawdata', function ($string) {
            return addcslashes($string, "\0..\37!@\177");
        });
        $twig->addFunction($function);
    }

    public function pluginsInitialized()
    {
        if($this->config->isEmpty('plugins.config.adminpanel.no_page')) {
            $this->config->push('pages.extra_paths', '@plugin/adminpanel/pages');
        }
    }

    public function outputGenerated($response)
    {
        // return if response is not successful
        if (!$response->isSuccessful()) {
            return;
        }

        if (!$this->isAdmin()) {
            if ($this->isAuthenticated()) {
                // prepend adminpanel to html body
                $controller = (0 === strpos(DI::get('Page')->path, '@post')) ? 'post' : 'page';
                $panel = DI::get('Twig')->render('@plugin/adminpanel/views/panel.twig', [
                    'controller' => $controller
                ]);
                $regex = '/<body(.*)>/';
                $replace = '<body$1>' . $panel;
                $content = preg_replace($regex, $replace, $response->getContent());
                $response->setContent($content);
            }
        } else {
            $action = $this->isAuthenticated() ? $this->request->getQuery('action', 'page/index') : 'login';
            $pos = strpos($action, '/');
            if ($pos === false) {
                $controller = 'default';
            } else {
                $controller = substr($action, 0, $pos);
                $action = substr($action, ++$pos);
            }

            $controllerClass = '\\herbie\\plugin\\adminpanel\\controllers\\' . ucfirst($controller) . 'Controller';
            $method = $action . 'Action';

            $controllerObject = new $controllerClass();
            if (!method_exists($controllerObject, $method)) {
                $controllerObject = new controllers\DefaultController();
                $method = 'errorAction';
            }
            $controllerObject->controller = $controller;
            $controllerObject->action = $action;

            $params = ['request' => $this->request];
            $content = call_user_func_array([$controllerObject, $method], $params);
            $response->setContent($content);
        }
    }

    /**
     * @return string
     */
    protected function getPathAlias()
    {
        return $this->config->get(
            'plugins.config.adminpanel.page.adminpanel',
            '@plugin/adminpanel/pages/adminpanel.html'
        );
    }

    protected function isAdmin()
    {
        return !empty(DI::get('Page')->adminpanel);
    }

    protected function isAuthenticated()
    {
        return !empty($_SESSION['LOGGED_IN']);
    }
}

(new AdminpanelPlugin)->install();
