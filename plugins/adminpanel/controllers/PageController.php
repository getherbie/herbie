<?php

namespace herbie\plugin\adminpanel\controllers;

use Herbie\Menu\Page;
use Herbie\Helper\FilesystemHelper;

class PageController extends Controller
{
    use PageControllerTrait;

    public function indexAction($query, $request)
    {
        $route = $query->get('route', '');
        $tree = $this->getPageTree()->findByRoute($route);
        return $this->render('page/index.twig', [
            'tree' => $tree,
            'cancel' => $route
        ]);
    }

    public function addAction($query, $request)
    {
        $title = $request->get('name');
        if(empty($title)) {
            $this->sendErrorHeader("Bitte einen Namen eingeben.");
        }
        $filename = FilesystemHelper::sanitizeFilename($title);
        $filepath = $this->app['alias']->get("@page/{$filename}.md");
        if(is_file($filepath)) {
            $this->sendErrorHeader("Eine Seite mit demselben Namen existiert schon.");
        }
        $eol = PHP_EOL;
        $data = "---{$eol}title: {$title}{$eol}hidden: 1{$eol}---{$eol}Meine neue Seite{$eol}";
        if(!file_put_contents($filepath, $data)) {
            $this->sendErrorHeader("Seite konnte nicht erstellt werden.");
        }
        return $this->indexAction($query, $request);
    }

    public function deleteAction($query, $request)
    {
        $file = $request->get('file');
        $filepath = $this->app['alias']->get($file);
        $basename = basename($filepath);
        if(empty($file)) {
            $this->sendErrorHeader('Ungültige Parameter!');
        }
        if(!is_file($filepath)) {
            $this->sendErrorHeader("Seite {$basename} konnte nicht gefunden werden.");
        }
        $tree = $this->getPageTree()->findBy('path', $file);
        $hasChildren = ($tree && $tree->hasChildren()) ? true : false;
        if($hasChildren) {
            $this->sendErrorHeader("Seite {$basename} hat Unterseiten und konnte nicht gelöscht werden.");
        }
        if(!@unlink($filepath)) {
            $this->sendErrorHeader("Seite {$basename} konnte nicht gelöscht werden.");
        }
        header('Content-Type: application/json');
        echo json_encode(true);
        exit;
    }

    protected function getPageTree()
    {
        $builder = new Page\Builder($this->app);
        $menu = $builder->buildCollection();
        return Page\Node::buildTree($menu);
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

    /*public function editAction($query, $request)
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
        if($this->app['request']->getMethod() == 'POST') {
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
    }*/

}