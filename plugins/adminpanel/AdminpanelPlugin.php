<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\adminpanel;

use Herbie;
use Herbie\Loader\FrontMatterLoader;
use Herbie\Menu;
use Twig_SimpleFunction;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Yaml\Yaml;


class AdminpanelPlugin extends Herbie\Plugin
{

    protected $content;

    protected $panel;

    protected $session;

    protected $request;

    public function init() {
        $this->session = new Session();
        $this->session->start();
        $this->request = $this->app['request'];
        $this->app['alias']->set('@media', '@web/media');
    }

    /**
     * @param Herbie\Event $event
     */
    public function onPluginsInitialized(Herbie\Event $event)
    {
        // add admin panel page
        $alias = $this->getPathAlias();
        $path = $this->app['alias']->get($alias);
        $loader = new FrontMatterLoader();
        $item = $loader->load($path);
        $item['path'] = $alias;
        $event['app']['menu']->addItem(
            new Menu\Page\Item($item)
        );
    }

    public function onOutputGenerated(Herbie\Event $event)
    {
        if(!$this->isAdmin()) {
            if ($this->isAuthenticated() && !empty($this->panel)) {
                $content = $event['response']->getContent();
                // replace body tag
                $regex = '/<body(.*)>/';
                $replace = '<body$1>' . $this->panel;
                $content = preg_replace($regex, $replace, $content);
                $event['response']->setContent($content);
            }
        } else {

            $action = $this->session->get('LOGGED_IN') ? $this->request->query->get('action', 'page/index') : 'login';
            $pos = strpos($action, '/');
            if($pos === false) {
                $controller = 'default';
                $method = $action . 'Action';
            } else {
                $controller = substr($action, 0, $pos);
                $method = substr($action, ++$pos) . 'Action';
            }

            $controllerClass = '\\herbie\\plugin\\adminpanel\\controllers\\' . ucfirst($controller) . 'Controller';

            $controller = new $controllerClass($this->app, $this->session);
            if(!method_exists($controller, $method)) {
                $controller = new controllers\DefaultController($this->app, $this->session);
                $method = 'errorAction';
            }
            $params = ['query' => $this->request->query, 'request' => $this->request->request];
            $content = call_user_func_array([$controller, $method], $params);
            $event['response']->setContent($content);
        }
    }

    public function onPageLoaded(Herbie\Event $event)
    {
        if(empty($this->app['page']->adminpanel)) {
            $this->panel = $this->app['twig']->render('@plugin/adminpanel/views/panel.twig');
        }
    }

    /**
     * @return string
     */
    protected function getPathAlias()
    {
        return $this->config(
            'plugins.adminpanel.page.adminpanel',
            '@plugin/adminpanel/pages/adminpanel.html'
        );
    }

    protected function isAdmin()
    {
        return !empty($this->app['menuItem']->adminpanel);
    }

    protected function isAuthenticated()
    {
        return (bool)$this->session->get('LOGGED_IN', false);
    }

}
