<?php

namespace herbie\plugin\adminpanel\controllers;

use Herbie;
use Herbie\Menu;
use Herbie\Helper\FilesystemHelper;
use Herbie\Loader\FrontMatterLoader;

class PostController extends Controller
{
    use PageControllerTrait;

    public function addAction($query, $request)
    {
        $title = $request->get('name');
        if(empty($title)) {
            $this->sendErrorHeader("Bitte einen Namen eingeben.");
        }
        $filename = date('Y-m-d-') . FilesystemHelper::sanitizeFilename($title);
        $filepath = $this->app['alias']->get("@post/{$filename}.md");
        if(is_file($filepath)) {
            $this->sendErrorHeader("Ein Blogpost mit demselben Namen existiert schon.");
        }
        $eol = PHP_EOL;
        $data = "---{$eol}title: {$title}{$eol}hidden: 1{$eol}---{$eol}Mein neuer Blogpost{$eol}";
        if(!file_put_contents($filepath, $data)) {
            $this->sendErrorHeader("Blogpost konnte nicht erstellt werden.");
        }
        return $this->indexAction();
    }

    public function indexAction()
    {
        $builder = new Menu\Post\Builder($this->app);
        return $this->render('post/index.twig', [
            'posts' => $builder->build()
        ]);
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
            $this->sendErrorHeader("Blogpost {$basename} konnte nicht gefunden werden.");
        }
        if(!@unlink($filepath)) {
            $this->sendErrorHeader("Blogpost {$basename} konnte nicht gelöscht werden.");
        }
        header('Content-Type: application/json');
        echo json_encode(true);
        exit;
    }

    public function editAction($query, $request)
    {
        $path = $query->get('path', null);
        $page = $this->app['pageLoader']->load($path, false, false);

        $unconfig = [];

        // Data config
        $data = $this->app['config']->get('plugins.config.adminpanel.fields', []);

        foreach($page['data'] as $key => $value) {
            if(!isset($data[$key])) {
                $data[$key] = [
                    'type' => 'label',
                    'label' => $key,
                    'value' => 'Feld nicht konfiguriert.'
                ];
                $unconfig[$key] = $value;
            } else {
                $data[$key]['value'] = $value;
            }
        }

        // Segment config
        $layouts = $this->app['config']->get('plugins.config.adminpanel.layouts', []);
        $layout = empty($data['layout']['value']) ? 'default.html' : $data['layout']['value'];
        $segments = [];
        foreach($layouts[$layout] as $pairs) {
            foreach($pairs as $key => $label) {
                if(empty($key)) {
                    $key = 0;
                }
                $segments[$key] = [
                    'label' => $label,
                    'value' => isset($page['segments'][$key]) ? $page['segments'][$key] : '',
                ];
            }
        }

        if(!empty($_POST)) {
            $postdata = array_merge($request->get('data', []), $unconfig);
            $postsegments = $request->get('segments', []);
            $this->app['pageLoader']->save($path, $postdata, $postsegments);
        }

        return $this->render('post/edit.twig', [
            'data' => $data,
            'segments' => $segments,
            'unconfig' => $unconfig
        ]);
    }

}