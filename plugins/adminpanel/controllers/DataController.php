<?php

namespace herbie\plugin\adminpanel\controllers;

use Symfony\Component\Yaml\Yaml;

class DataController extends Controller
{

    public function addAction($query, $request)
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

    public function indexAction()
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

    public function deleteAction($query, $request)
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

    public function editAction($query, $request)
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
        if($this->app['request']->getMethod() == 'POST') {
            $data = $request->get('data', []);
            #echo"<pre>";print_r($data);echo"</pre>";
            $content = Yaml::dump(array_values($data), 100, 4);
            $saved = file_put_contents($absPath, $content);
        }

        #echo"<pre>";print_r(Yaml::parse(file_get_contents($absPath)));echo"</pre>";

        return $this->render('data/edit.twig', [
            'config' => $config,
            'data' => Yaml::parse(file_get_contents($absPath)),
            'saved' => $saved
        ]);
    }

    protected function editAsString($query, $request)
    {
        $path = $query->get('path', null);
        $absPath = $this->app['alias']->get($path);

        $saved = false;
        if($this->app['request']->getMethod() == 'POST') {
            $content = $request->get('content', null);
            $saved = file_put_contents($absPath, $content);
        }

        return $this->render('data/editstring.twig', [
            'content' => file_get_contents($absPath),
            'saved' => $saved
        ]);
    }

}