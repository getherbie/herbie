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
                case 'data/add':
                    $content = $this->dataAddAction();
                    break;
                case 'data/edit':
                    $content = $this->dataEditAction();
                    break;
                case 'data/index':
                    $content = $this->dataIndexAction();
                    break;
                case 'file/delete':
                    $this->fileDeleteAction();
                    break;
                case 'page/edit':
                    $content = $this->pageEditAction();
                    break;
                case 'page/index':
                case '': // index
                    $content = $this->render('page/index.twig', []);
                    break;
                case 'post/index':
                    $content = $this->render('post/index.twig', []);
                    break;
                case 'login':
                    $content = $this->loginAction();
                    break;
                case 'logout':
                    $this->session->set('LOGGED_IN', false);
                    $this->app['twig']->environment->getExtension('herbie')->functionRedirect('');
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

    protected function dataAddAction()
    {
        $name = strtolower(trim($this->request->request->get('name')));
        $path = $this->app['alias']->get("@site/data/{$name}.yml");
        $dir = dirname($path);
        if(empty($name)) {
            $this->sendErrorHeader('Bitte einen Namen eingeben.');
        } elseif(is_file($path)) {
            $this->sendErrorHeader('Eine gleichnamige Datei ist schon vorhanden.');
        } elseif(!is_dir($dir)) {
            $this->sendErrorHeader("Verzeichnis {$dir} existiert nicht.");
        } elseif(!is_writable($dir)) {
            $this->sendErrorHeader("Verzeichnis {$dir} ist nicht schreibbar.");
        } elseif(!fclose(fopen($path, "x"))) {
            $this->sendErrorHeader("Datei {$name} konnte nicht erstellt werden.");
        } else {
            header('Content-Type: application/json');
            echo json_encode($name);
            exit;
        }
    }

    protected function sendErrorHeader($message, $exit = true, $code = 418)
    {
        header("HTTP/1.0 $code $message");
        if ($exit) {
            exit;
        }
    }

    protected function fileDeleteAction()
    {
        $deleted = false;
        $path = $this->request->request->get('path');
        if(!empty($path) && (substr($path, 0, 1) == '@')) {
            $absPath = $this->app['alias']->get($path);
            if(is_file($absPath)) {
                $deleted = unlink($absPath);
            }
        }
        header('Content-Type: application/json');
        echo json_encode($deleted);
        exit;
    }

    protected function pageEditAction()
    {
        $path = $this->request->query->get('path', null);

        $data = $this->app['pageLoader']->load($path, false);

        $absPath = $this->app['alias']->get($path);
        $action = strpos($path, '@page') !== false ? 'page/index' : 'post/index';

        if(is_null($path)) {
            throw new \Exception('Path must be set');
        }

        $data = $this->request->request->get('data', file_get_contents($absPath));
        $content = $this->request->request->get('content', file_get_contents($absPath));

        $saved = false;
        if($this->request->getMethod() == 'POST') {
            $saved = file_put_contents($absPath, $content);

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
            'action' => $action,
            'saved' => $saved
        ]);
    }

    protected function dataIndexAction()
    {
        $data = $this->app['data'];
        foreach($data as $key => $unused) {
            $path = $this->app['alias']->get('@site/data/' . $key . '.yml');
            $data[$key] = [
                'name' => $key,
                'size' => is_readable($path) ? filesize($path) : 0,
                'created' => filectime($path),
                'modified' => filemtime($path)
            ];
        };
        return $this->render('data/index.twig', [
            'data' => $data
        ]);
    }

    protected function dataEditAction()
    {
        $path = $this->request->query->get('path', null);
        $absPath = $this->app['alias']->get($path);

        // Config
        $name = pathinfo($absPath, PATHINFO_FILENAME);
        $config = $this->app['config']->get('plugins.adminpanel.data.' . $name . '.config');
        if(is_null($config)) {
            return $this->dataEditAsString();
        }

        $saved = false;
        if($this->request->getMethod() == 'POST') {
            $data = $this->request->request->get('data', []);
            #echo"<pre>";print_r($data);echo"</pre>";
            $content = Yaml::dump(array_values($data));
            $saved = file_put_contents($absPath, $content);
        }

        #echo"<pre>";print_r(Yaml::parse(file_get_contents($absPath)));echo"</pre>";

        return $this->render('data/edit.twig', [
            'config' => $config,
            'data' => Yaml::parse(file_get_contents($absPath)),
            'saved' => $saved
        ]);
    }

    protected function dataEditAsString()
    {
        $path = $this->request->query->get('path', null);
        $absPath = $this->app['alias']->get($path);

        $saved = false;
        if($this->request->getMethod() == 'POST') {
            $content = $this->request->request->get('content', null);
            $saved = file_put_contents($absPath, $content);
        }

        return $this->render('data/editstring.twig', [
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
