<?php

namespace herbie\plugin\adminpanel\controllers;

use Herbie;
use Herbie\Menu;
use Herbie\Loader\FrontMatterLoader;

class PostController extends Controller
{

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
        return $this->postIndexAction();
    }

    public function dataAction($query, $request)
    {
        $alias = $query->get('path');
        $path = $this->app['alias']->get($alias);

        $loader = new FrontMatterLoader();
        $data = $loader->load($path);

        $fields = $this->app['config']->get('plugins.adminpanel.fields', []);
        $unconfig = [];

        foreach($data as $key => $value) {
            if(empty($fields[$key]['type'])) {
                $fields[$key] = [
                    'label' => ucfirst($key)
                ];
                $unconfig[$key] = $value;
            }
        }

        $saved = false;
        if(!empty($_POST)) {
            $update = array_merge($request->get('data', []), $unconfig);
            $update = ArrayHelper::filterEmptyElements($update);
            if(Herbie\Helper\PageHelper::updateData($path, $update)) {
                $data = $loader->load($path);
                $saved = true;
            }
        }

        return $this->render('post/data.twig', [
            'data' => $data,
            'fields' => $fields,
            'saved' => $saved
        ]);
    }

    public function contentAction($query, $request)
    {
        $alias = $query->get('path', null);
        $path = $this->app['alias']->get($alias);

        $saved = false;
        if(!empty($_POST)) {
            $segments = $request->get('segments', []);
            if(Herbie\Helper\PageHelper::updateSegments($path, $segments)) {
                $saved = true;
            }
        }

        $page = $this->app['pageLoader']->load($path, false, false);
        $data = $page['data'];

        // Segment config
        $layouts = $this->app['config']->get('plugins.adminpanel.layouts', []);
        $layout = empty($data['layout']) ? 'default.html' : $data['layout'];

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

        /*
        $contents = file_get_contents($path);
        $length = strlen($contents);
        for($i=0; $i<$length; $i++) {
            echo $contents[$i] . ' = ' . ord($contents[$i]) . '<br>';
        }
        echo '=====<hr>';

        foreach($page['segments'] as $key => $value) {
            echo "--------<br>";
            $length = strlen($value);
            for($i=0; $i<$length; $i++) {
                echo $value[$i] . ' = ' . ord($value[$i]) . '<br>';
            }
        }
        */

        #echo"<pre>";print_r(preg_replace('/\R/', '\n'."\n", $content));echo"</pre>";
        /*foreach($page['segments'] as $segment) {
            echo '<div style="border-top:1px solid black">';
            echo preg_replace('/\r?\n/', '\n<br>', $segment);
            echo '</div>';
        }*/

        return $this->render('post/content.twig', [
            'segments' => $segments,
            'page' => $page,
            'saved' => $saved
        ]);
    }

    public function editAction($query, $request)
    {
        $path = $query->get('path', null);
        $page = $this->app['pageLoader']->load($path, false, false);

        echo"<pre style='margin-left:200px'>";print_r($page);echo"</pre>";

        // Testing
        #unset($page['data']['categories']);
        #unset($page['data']['tags']);
        #echo"<pre>";print_r($page);echo"</pre>";

        $unconfig = [];

        // Data config
        $data = $this->app['config']->get('plugins.adminpanel.fields', []);
        echo"<pre style='margin-left:200px'>";print_r($data);echo"</pre>";

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
        echo"<pre style='margin-left:200px'>";print_r($unconfig);echo"</pre>";

        // Segment config
        $layouts = $this->app['config']->get('plugins.adminpanel.layouts', []);
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
            #echo"<pre>";print_r($postdata);echo"</pre>";
            $this->app['pageLoader']->save($path, $postdata, $postsegments);
        }

        return $this->render('post/edit.twig', [
            'data' => $data,
            'segments' => $segments,
            'unconfig' => $unconfig
        ]);
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
            $this->sendErrorHeader("Blogpost {$$basename} konnte nicht gefunden werden.");
        }
        if(!@unlink($filepath)) {
            $this->sendErrorHeader("Blogpost {$basename} konnte nicht gelöscht werden.");
        }
        header('Content-Type: application/json');
        echo json_encode(true);
        exit;
    }

}