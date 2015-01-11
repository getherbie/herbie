<?php

namespace herbie\plugin\adminpanel\controllers;

use Herbie\Helper\FilesystemHelper;
use Symfony\Component\Yaml\Yaml;

class ToolsController extends Controller
{

    public function indexAction($query, $request)
    {
        #print_r($this->app['config']);
        return $this->render('tools/index.twig', [
            'cacheDirs' => $this->getCacheDirs(),
            'yamlFiles' => $this->getYamlFiles()
        ]);
    }

    public function deleteCacheAction($query, $request)
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

        if(!FilesystemHelper::rrmdir($path)) {
            $this->sendErrorHeader("{$label} wurde nicht oder nur teilweise gelöscht.");
        }

        echo "Verzeichnis wurde geleert.";
        exit;
    }

    public function reformatFileAction($query, $request)
    {
        $name = $request->get('name');
        $files = $this->getYamlFiles();
        if(empty($name) || !array_key_exists($name, $files)) {
            $this->sendErrorHeader('Ungültiger Aufruf.');
        }
        if(!is_file($files[$name]['path'])) {
            $this->sendErrorHeader("{$files[$name]['label']} existiert nicht.");
        }
        if(!FilesystemHelper::createBackupFile($files[$name]['path'])) {
            $this->sendErrorHeader("Backup-Datei konnte nicht erstellt werden.");
        }
        $parsed = Yaml::parse($files[$name]['path']);
        $content = Yaml::dump($parsed, 100, 4);
        if(!file_put_contents($files[$name]['path'], $content)) {
            $this->sendErrorHeader("Datei konnte nicht erstellt werden.");
        }
        echo "Datei wurde neu formatiert.";
        exit;
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
                    'count' => FilesystemHelper::rcount($path)
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