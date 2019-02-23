<?php

namespace herbie\plugin\adminpanel\controllers;

use Herbie\Helper\FilesystemHelper;
use Herbie\Yaml;

class ToolsController extends Controller
{

    public function indexAction($request)
    {
        return $this->render('tools/index.twig', [
            'cacheDirs' => $this->getCacheDirs(),
            'yamlFiles' => $this->getYamlFiles()
        ]);
    }

    public function deleteCacheAction($request)
    {
        $name = $request->getPost('name');
        $dirs = $this->getCacheDirs();
        if (empty($name) || !array_key_exists($name, $dirs)) {
            $this->sendErrorHeader($this->t('Invalid parameter!'));
        }
        /**
         * @param $label
         * @param $path
         * @param $count
         */
        extract($dirs[$name]);
        if (!is_dir($path)) {
            $this->sendErrorHeader($this->t('{name} does not exist.', ['{name}' => $label]));
        }

        if (!FilesystemHelper::rrmdir($path)) {
            $this->sendErrorHeader($this->t('{name} can not be deleted.', ['{name}' => $label]));
        }

        echo $this->t('Folder was emptied.');
        exit;
    }

    public function reformatFileAction($request)
    {
        $name = $request->getPost('name');
        $files = $this->getYamlFiles();
        if (empty($name) || !array_key_exists($name, $files)) {
            $this->sendErrorHeader($this->t('Invalid parameter!'));
        }
        if (!is_file($files[$name]['path'])) {
            $this->sendErrorHeader($this->t('{name} does not exist.', ['{name}' => $files[$name]['label']]));
        }
        if (!FilesystemHelper::createBackupFile($files[$name]['path'])) {
            $this->sendErrorHeader($this->t('Backup file can not be created.'));
        }
        $parsed = Yaml::parse($files[$name]['path']);
        $content = Yaml::dump($parsed);
        if (!file_put_contents($files[$name]['path'], $content)) {
            $this->sendErrorHeader($this->t('File can not be created.'));
        }
        echo $this->t('File was formatted and saved.');
        exit;
    }

    protected function getCacheDirs()
    {
        $config = $this->config;
        $tempDirs = [
            ['site/data/cache', $this->t('Data cache'), $config->get('cache.data.dir')],
            ['site/page/cache', $this->t('Page cache'), $config->get('cache.page.dir')],
            ['site/twig/cache', $this->t('Twig cache'), $config->get('twig.cache')],
            ['web/assets', $this->t('Web assets'), $this->alias->get('@web/assets')],
            ['web/cache', $this->t('Web cache'), $this->alias->get('@web/cache')]
        ];
        $dirs = [];
        foreach ($tempDirs as $td) {
            list($key, $label, $path) = $td;
            if (!empty($path) && is_dir($path)) {
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
        $file = $this->alias->get('@site/config/main.yml');
        if (is_file($file)) {
            $dirs['config'] = [
                'label' => $this->t('Site config'),
                'path' => $file
            ];
        }
        return $dirs;
    }
}
