<?php

namespace herbie\plugin\adminpanel\controllers;

use Herbie;
use Herbie\Menu;
use Herbie\Helper\FilesystemHelper;
use Herbie\Loader\FrontMatterLoader;

class PostController extends Controller
{
    use PageControllerTrait;

    public function addAction($request)
    {
        $title = $request->getPost('name');
        if (empty($title)) {
            $this->sendErrorHeader($this->t('Name cannot be empty.'));
        }
        $filename = date('Y-m-d-') . FilesystemHelper::sanitizeFilename($title);
        $filepath = $this->alias->get("@post/{$filename}.md");
        if (is_file($filepath)) {
            $this->sendErrorHeader($this->t('A page with the same name already exists.'));
        }
        $eol = PHP_EOL;
        $pageTitle = $this->t('My new blog post');
        $data = "---{$eol}title: {$title}{$eol}disabled: 1{$eol}hidden: 1{$eol}---{$eol}{$pageTitle}{$eol}";
        if (!file_put_contents($filepath, $data)) {
            $this->sendErrorHeader($this->t('Page {name} can not be created.', ['{name}' => $title]));
        }
        return $this->indexAction();
    }

    public function indexAction()
    {
        $builder = new Menu\Post\Builder($this->getService('Cache\DataCache'), $this->config);
        return $this->render('post/index.twig', [
            'posts' => $builder->build(),
            'dir' => $this->config->get('posts.path')
        ]);
    }

    public function deleteAction($request)
    {
        $file = $request->getPost('file');
        $filepath = $this->alias->get($file);
        $basename = basename($filepath);
        if (empty($file)) {
            $this->sendErrorHeader($this->t('Invalid parameter!'));
        }
        if (!is_file($filepath)) {
            $this->sendErrorHeader($this->t('Page {name} does not exist.', ['{name}' => $basename]));
        }
        if (!@unlink($filepath)) {
            $this->sendErrorHeader($this->t('Page {name} can not be deleted.', ['{name}' => $basename]));
        }
        header('Content-Type: application/json');
        echo json_encode(true);
        exit;
    }

    public function editAction($request)
    {
        $path = $request->getQuery('path', null);
        $page = $this->getService('Loader\PageLoader')->load($path, false);

        $unconfig = [];

        // Data config
        $data = $this->config->get('plugins.config.adminpanel.fields', []);

        foreach ($page['data'] as $key => $value) {
            if (!isset($data[$key])) {
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
        $layouts = $this->config->get('plugins.config.adminpanel.layouts', []);
        $layout = empty($data['layout']['value']) ? 'default.html' : $data['layout']['value'];
        $segments = [];
        foreach ($layouts[$layout] as $pairs) {
            foreach ($pairs as $key => $label) {
                if (empty($key)) {
                    $key = 0;
                }
                $segments[$key] = [
                    'label' => $label,
                    'value' => isset($page['segments'][$key]) ? $page['segments'][$key] : '',
                ];
            }
        }

        if (!empty($_POST)) {
            $postdata = array_merge($request->getPost('data', []), $unconfig);
            $postsegments = $request->getPost('segments', []);
            $this->getService('Loader\PageLoader')->save($path, $postdata, $postsegments);
        }

        return $this->render('post/edit.twig', [
            'data' => $data,
            'segments' => $segments,
            'unconfig' => $unconfig
        ]);
    }
}
