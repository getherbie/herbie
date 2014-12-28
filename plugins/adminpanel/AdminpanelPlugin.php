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
            $method = str_replace('/', '', $action) . 'Action';
            if(!method_exists($this, $method)) {
                $method = 'errorAction';
                if($this->request->isXmlHttpRequest()) {
                    $this->sendErrorHeader('Ungültiger Action-Parameter');
                }
            }
            $params = ['query' => $this->request->query, 'request' => $this->request->request];
            $content = call_user_func_array([$this, $method], $params);
            $event['response']->setContent($content);
        }
    }

    public function onPageLoaded(Herbie\Event $event)
    {
        if(empty($this->app['page']->adminpanel)) {
            $this->panel = $this->app['twig']->render('@plugin/adminpanel/templates/panel.twig');
        }
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

    protected function errorAction($query)
    {
        return $this->render('default/error.twig', []);
    }

    protected function mediaAddFolderAction($query, $request)
    {
        $dir = strtolower(trim($request->get('dir')));
        $name = strtolower(trim($request->get('name')));
        $path = $this->app['alias']->get('@media/' . $dir . '/' . $name);
        if(empty($name)) {
            $this->sendErrorHeader('Bitte einen Namen eingeben.');
        }
        if(is_dir($path)) {
            $this->sendErrorHeader('Ein gleichnamiger Ordner ist schon vorhanden.');
        }
        if(!@mkdir($path)) {
            $this->sendErrorHeader('Ordner konnte nicht erstellt werden.');
        }
        $query->add(['dir' => $dir]);
        return $this->mediaIndexAction($query, $request);
    }

    protected function mediaIndexAction($query, $request)
    {
        $dir = $query->get('dir', '');
        $dir = str_replace(['../', '..', './', '.'], '', trim($dir, '/'));
        $path = $this->app['alias']->get('@media/' . $dir);
        $root = $this->app['alias']->get('@media');

        $iterator = null;
        if(is_dir($path)) {
            $directoryIterator = new Herbie\Iterator\DirectoryIterator($path, $root);
            $iterator = new Herbie\Iterator\DirectoryDotFilter($directoryIterator);
        }

        return $this->render('media/index.twig', [
            'iterator' => $iterator,
            'dir' => $dir,
            'parentDir' => str_replace('.', '', dirname($dir)),
        ]);
    }

    protected function mediaDeleteAction($query, $request)
    {
        $path = $request->get('file');
        $path = str_replace(['../', '..', './'], '', trim($path, '/'));
        $absPath = $this->app['alias']->get('@media/' . $path);
        $name = basename($absPath);

        if(is_file($absPath) && !@unlink($absPath)) {
            $this->sendErrorHeader("Datei {$name} konnte nicht gelöscht werden.");
        } elseif(is_dir($absPath) && !@rmdir($absPath)) {
            if(count(scandir($absPath)) >= 2) {
                $this->sendErrorHeader("Ordner {$name} enthält Dateien und konnte nicht gelöscht werden.");
            }
            $this->sendErrorHeader("Ordner {$name} konnte nicht gelöscht werden.");
        }
        header('Content-Type: application/json');
        echo json_encode(true);
        exit;
    }

    protected function mediaUploadAction($query, $request)
    {
        $data = array();
        $dir = strtolower(trim($request->get('dir')));

        if(!empty($_FILES)) {
            $files = array();

            $uploaddir = $this->app['alias']->get("@media/{$dir}/");
            foreach($_FILES as $file)
            {
                if(move_uploaded_file($file['tmp_name'], $uploaddir . basename($file['name']))) {
                    $files[] = $uploaddir . $file['name'];
                } else {
                    $this->sendErrorHeader('Beim Upload ist ein Fehler aufgetreten.');
                }
            }
            $data = array('files' => $files);
        } else {
            $this->sendErrorHeader('Bitte eine oder mehrere Dateien auswählen.');
        }

        $query->add(['dir' => $dir]);
        $data['html'] = $this->mediaIndexAction($query, $request);

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function postAddAction($query, $request)
    {
        $title = $request->get('name');
        if(empty($title)) {
            $this->sendErrorHeader("Bitte einen Namen eingeben.");
        }
        $filename = date('Y-m-d-') . Herbie\Helper\Filesystem::sanitizeFilename($title);
        $filepath = $this->app['alias']->get("@post/{$filename}.md");
        if(is_file($filepath)) {
            $this->sendErrorHeader("Ein Blogpost mit demselben Namen existiert schon.");
        }
        $eol = PHP_EOL;
        $data = "---{$eol}title: {$title}{$eol}hidden: 1{$eol}---{$eol}Mein neuer Blogpost{$eol}";
        if(!file_put_contents($filepath, $data)) {
            $this->sendErrorHeader("Blogpost konnte nicht erstellt werden.");
        }
        
        // Post refreshen
        
        return $this->postIndexAction();
    }

    protected function postIndexAction()
    {
		$builder = new Menu\Post\Builder($this->app);
        return $this->render('post/index.twig', [
        	'posts' => $builder->build()
        ]);
    }

    protected function postDeleteAction($query, $request)
    {
        $file = $request->get('file');
        $filepath = $this->app['alias']->get($file);
        $basename = basename($filepath);
        if(empty($file)) {
            $this->sendErrorHeader('Ungültige Parameter!');
        }
        if(!is_file($filepath)) {
            $this->sendErrorHeader("Blogpost {$$basename} konnte nicht gefunden werden.");
        }
        if(!@unlink($filepath)) {
            $this->sendErrorHeader("Blogpost {$basename} konnte nicht gelöscht werden.");
        }
        header('Content-Type: application/json');
        echo json_encode(true);
        exit;
    }

    protected function pageIndexAction()
    {
        return $this->render('page/index.twig', []);
    }

    protected function pageEditAction($query, $request)
    {
        $path = $query->get('path', null);

        $data = $this->app['pageLoader']->load($path, false);

        $absPath = $this->app['alias']->get($path);
        $action = strpos($path, '@page') !== false ? 'page/index' : 'post/index';

        if(is_null($path)) {
            throw new \Exception('Path must be set');
        }

        $data = $request->get('data', file_get_contents($absPath));
        $content = $request->get('content', file_get_contents($absPath));

        $saved = false;
        if($this->request->getMethod() == 'POST') {
            $saved = file_put_contents($absPath, $content);

            if ($request->get('button2') !== null) {
                $this->app['twig']->environment->getExtension('herbie')->functionRedirect('adminpanel?action=' . $action);
            }

            if ($request->get('button3') !== null) {
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

    protected function dataAddAction($query, $request)
    {
        $name = strtolower(trim($request->get('name')));
        $path = $this->app['alias']->get("@site/data/{$name}.yml");
        $dir = dirname($path);
        if(empty($name)) {
            $this->sendErrorHeader('Bitte einen Namen eingeben.');
        }
        if(is_file($path)) {
            $this->sendErrorHeader('Eine gleichnamige Datei ist schon vorhanden.');
        }
        if(!is_dir($dir)) {
            $this->sendErrorHeader("Verzeichnis {$dir} existiert nicht.");
        }
        if(!is_writable($dir)) {
            $this->sendErrorHeader("Verzeichnis {$dir} ist nicht schreibbar.");
        }
        if(!fclose(fopen($path, "x"))) {
            $this->sendErrorHeader("Datei {$name} konnte nicht erstellt werden.");
        }
        return $this->dataIndexAction($query, $request);
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

    protected function dataDeleteAction($query, $request)
    {
        $file = $request->get('file');
        $absPath = $this->app['alias']->get('@site/data/' . $file . '.yml');

        if(!is_file($absPath)) {
            $this->sendErrorHeader("Datei {$absPath} ist nicht vorhanden.");
        }
        if(!@unlink($absPath)) {
            $this->sendErrorHeader("Datei {$file} konnte nicht gelöscht werden.");
        }

        header('Content-Type: application/json');
        echo json_encode(true);
        exit;
    }

    protected function dataEditAction($query, $request)
    {
        $path = $query->get('path', null);
        $absPath = $this->app['alias']->get($path);

        // Config
        $name = pathinfo($absPath, PATHINFO_FILENAME);
        $config = $this->app['config']->get('plugins.adminpanel.data.' . $name . '.config');
        if(is_null($config)) {
            return $this->dataEditAsString($query, $request);
        }

        $saved = false;
        if($this->request->getMethod() == 'POST') {
            $data = $request->get('data', []);
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

    protected function dataEditAsString($query, $request)
    {
        $path = $query->get('path', null);
        $absPath = $this->app['alias']->get($path);

        $saved = false;
        if($this->request->getMethod() == 'POST') {
            $content = $request->get('content', null);
            $saved = file_put_contents($absPath, $content);
        }

        return $this->render('data/editstring.twig', [
            'content' => file_get_contents($absPath),
            'saved' => $saved
        ]);
    }

    public function loginAction($query, $request)
    {
        if($this->request->getMethod() == 'POST') {
            $password = $request->get('password', null);
            if(md5($password) == $this->app['config']->get('plugins.adminpanel.password')) {
                $this->session->set('LOGGED_IN', true);
                $this->app['twig']->environment->getExtension('herbie')->functionRedirect('adminpanel');
            }
        }
        return $this->render('default/login.twig', []);
    }

    protected function logoutAction()
    {
        $this->session->set('LOGGED_IN', false);
        $this->app['twig']->environment->getExtension('herbie')->functionRedirect('');
    }

    public function toolsIndexAction($query, $request)
    {
        #print_r($this->app['config']);
        return $this->render('tools/index.twig', [
            'cacheDirs' => $this->getCacheDirs(),
            'yamlFiles' => $this->getYamlFiles()
        ]);
    }

    public function toolsDeleteCacheAction($query, $request)
    {
        $name = $request->get('name');
        $dirs = $this->getCacheDirs();
        if(empty($name) || !array_key_exists($name, $dirs)) {
            $this->sendErrorHeader('Ungültiger Aufruf.');
        }
        /**
         * @param $label
         * @param $path
         * @param $count
         */
        extract($dirs[$name]);
        if(!is_dir($path)) {
            $this->sendErrorHeader("{$label} existiert nicht.");
        }

        if(!Herbie\Helper\Filesystem::rrmdir($path)) {
            $this->sendErrorHeader("{$label} wurde nicht oder nur teilweise gelöscht.");
        }

        echo "Verzeichnis wurde geleert.";
        exit;
    }

    public function toolsReformatFileAction($query, $request)
    {
        $name = $request->get('name');
        $files = $this->getYamlFiles();
        if(empty($name) || !array_key_exists($name, $files)) {
            $this->sendErrorHeader('Ungültiger Aufruf.');
        }
        if(!is_file($files[$name]['path'])) {
            $this->sendErrorHeader("{$files[$name]['label']} existiert nicht.");
        }
        if(!Herbie\Helper\Filesystem::createBackupFile($files[$name]['path'])) {
            $this->sendErrorHeader("Backup-Datei konnte nicht erstellt werden.");
        }
        $parsed = Yaml::parse($files[$name]['path']);
        $content = Yaml::dump($parsed, 100, 4, false, false);
        if(!file_put_contents($files[$name]['path'], $content)) {
            $this->sendErrorHeader("Datei konnte nicht erstellt werden.");
        }
        echo "Datei wurde neu formatiert.";
        exit;
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

    protected function getCacheDirs()
    {
        $config = $this->app['config'];
        $tempDirs = [
            ['site/data/cache', 'Daten-Cache', $config->get('cache.data.dir')],
            ['site/page/cache', 'Seiten-Cache', $config->get('cache.page.dir')],
            ['site/twig/cache', 'Twig-Cache', $config->get('twig.cache')],
            ['web/assets', 'Web-Assets', $this->app['alias']->get('@web/assets')],
            ['web/cache', 'Web-Cache', $this->app['alias']->get('@web/cache')]
        ];
        $dirs = [];
        foreach($tempDirs as $td) {
            list($key, $label, $path) = $td;
            if(!empty($path) && is_dir($path)) {
                $dirs[$key] = [
                    'label' => $label,
                    'path' => $path,
                    'count' => Herbie\Helper\Filesystem::rcount($path)
                ];
            }
        }
        return $dirs;
    }

    protected function getYamlFiles()
    {
        $dirs = [];
        $file = $this->app['alias']->get('@site/config.yml');
        if(is_file($file)) {
            $dirs['config'] = [
                'label' => 'Site-Config',
                'path' => $file
            ];
        }
        return $dirs;
    }

}
