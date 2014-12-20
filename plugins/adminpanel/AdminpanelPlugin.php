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
            $action = $this->session->get('LOGGED_IN') ? $this->request->query->get('action') : 'login';
            switch($action) {
                case 'editpage':
                    $content = $this->editpageAction();
                    break;
                case '': // index
                case 'pages':
                    $content = $this->render('pages.twig', []);
                    break;
                case 'posts':
                    $content = $this->render('posts.twig', []);
                    break;
                case 'data':
                    $content = $this->render('data.twig', []);
                    break;
                case 'editdata':
                    $content = $this->editdataAction();
                    break;
                case 'logout':
                    $this->session->set('LOGGED_IN', false);
                    $this->app['twig']->environment->getExtension('herbie')->functionRedirect('');
                    break;
                case 'login':
                    $content = $this->loginAction();
                    break;
                default:
                    $content = $this->render('error.twig', []);
            }
            $event['response']->setContent($content);
        }
    }

    public function onPageLoaded(Herbie\Event $event)
    {
        if(empty($this->app['page']->adminpanel)) {
            $this->panel = $this->app['twig']->render('@plugin/adminpanel/templates/panel.twig');
        }
    }

    protected function editpageAction()
    {
        $path = $this->request->query->get('path', null);

        $data = $this->app['pageLoader']->load($path, false);

        $absPath = $this->app['alias']->get($path);
        $action = strpos($path, '@page') !== false ? 'pages' : 'posts';

        if(is_null($path)) {
            throw new \Exception('Path must be set');
        }

        $data = $this->request->request->get('data', file_get_contents($absPath));
        $content = $this->request->request->get('content', file_get_contents($absPath));

        if($this->request->getMethod() == 'POST') {
            file_put_contents($absPath, $content);

            if ($this->request->request->get('button2') !== null) {
                $this->app['twig']->environment->getExtension('herbie')->functionRedirect('adminpanel?action=' . $action);
            }

            if ($this->request->request->get('button3') !== null) {
                $this->redirectBack($path);
            }
        }

        return $this->render('form.twig', [
            'data' => $data,
            'content' => $content,
            'path' => $path,
            'action' => $action
        ]);
    }

    protected function editdataAction()
    {
        $path = $this->request->query->get('path', null);
        $absPath = $this->app['alias']->get($path);

        // Config
        $name = pathinfo($absPath, PATHINFO_FILENAME);
        $config = $this->app['config']->get('plugins.adminpanel.data.' . $name . '.config');
        if(is_null($config)) {
            return $this->editDataAsString();
        }

        $saved = false;
        if($this->request->getMethod() == 'POST') {
            $data = $this->request->request->get('data', []);
            #echo"<pre>";print_r($data);echo"</pre>";
            $content = Yaml::dump(array_values($data));
            $saved = file_put_contents($absPath, $content);
        }

        #echo"<pre>";print_r(Yaml::parse(file_get_contents($absPath)));echo"</pre>";

        return $this->render('editdata.twig', [
            'config' => $config,
            'data' => Yaml::parse(file_get_contents($absPath)),
            'saved' => $saved
        ]);
    }

    protected function editDataAsString()
    {
        $path = $this->request->query->get('path', null);
        $absPath = $this->app['alias']->get($path);

        $saved = false;
        if($this->request->getMethod() == 'POST') {
            $content = $this->request->request->get('content', null);
            $saved = file_put_contents($absPath, $content);
        }

        return $this->render('editdata_string.twig', [
            'content' => file_get_contents($absPath),
            'saved' => $saved
        ]);
    }

    public function loginAction()
    {
        if($this->request->getMethod() == 'POST') {
            $password = $this->request->request->get('password', null);
            if(md5($password) == $this->app['config']->get('plugins.adminpanel.password')) {
                $this->session->set('LOGGED_IN', true);
                $this->app['twig']->environment->getExtension('herbie')->functionRedirect('adminpanel');
            }
        }
        return $this->render('login.twig', []);
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

    protected function redirectBack($path)
    {
        $item = $this->app['menu']->find($path, 'path');
        if(is_null($item)) {
            $item = $this->app['posts']->find($path, 'path');
        }
        if(isset($item)) {
            $route = $item->route;
        } else {
            $route = '';
        }
        $this->app['twig']->environment->getExtension('herbie')->functionRedirect($route);
    }

    protected function render($template, $params = [])
    {
        return $this->app['twig']->render(
            '@plugin/adminpanel/templates/' . $template,
            $params
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
