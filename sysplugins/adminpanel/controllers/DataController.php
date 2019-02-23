<?php

namespace herbie\plugin\adminpanel\controllers;

use Herbie\Yaml;

class DataController extends Controller
{

    public function addAction($request)
    {
        $name = strtolower(trim($request->getPost('name')));
        $path = $this->alias->get("@site/data/{$name}.yml");
        $dir = dirname($path);
        if (empty($name)) {
            $this->sendErrorHeader($this->t('Name cannot be empty.'));
        }
        if (is_file($path)) {
            $this->sendErrorHeader($this->t('A file with the same name already exists.'));
        }
        if (!is_dir($dir)) {
            $this->sendErrorHeader($this->t('Directory {dir} does not exist.', ['{dir}' => $dir]));
        }
        if (!is_writable($dir)) {
            $this->sendErrorHeader($this->t('Directory {dir} is not writable.', ['{dir}' => $dir]));
        }
        if (!fclose(fopen($path, "x"))) {
            $this->sendErrorHeader($this->t('File {name} can not be created.', ['{name}' => $name]));
        }
        return $this->indexAction($request);
    }

    public function indexAction()
    {
        $dir = $this->alias->get('@site/data/');
        $data = $this->getService('DataArray');
        foreach ($data as $key => $unused) {
            $path = $dir . $key . '.yml';
            $data[$key] = [
                'name' => $key,
                'size' => is_readable($path) ? filesize($path) : 0,
                'created' => filectime($path),
                'modified' => filemtime($path)
            ];
        };
        return $this->render('data/index.twig', [
            'data' => $data,
            'dir' => $dir
        ]);
    }

    public function deleteAction($request)
    {
        $file = $request->getPost('file');
        $absPath = $this->alias->get('@site/data/' . $file . '.yml');

        if (!is_file($absPath)) {
            $this->sendErrorHeader($this->t('File {file} does not exist.', ['{file}' => $file]));
        }
        if (!@unlink($absPath)) {
            $this->sendErrorHeader($this->t('File {file} can not be deleted.', ['{file}' => $file]));
        }

        header('Content-Type: application/json');
        echo json_encode(true);
        exit;
    }

    public function editAction($request)
    {
        $path = $request->getQuery('path', null);
        $absPath = $this->alias->get($path);

        // Config
        $name = pathinfo($absPath, PATHINFO_FILENAME);
        $config = $this->config->get('plugins.config.adminpanel.data.' . $name . '.config');
        if (is_null($config)) {
            return $this->editAsString($request);
        }

        $saved = false;
        if ($this->request->getMethod() == 'POST') {
            $data = $request->getPost('data', []);
            $content = Yaml::dump(array_values($data));
            $saved = file_put_contents($absPath, $content);
        }

        return $this->render('data/edit.twig', [
            'config' => $config,
            'data' => Yaml::parseFile($absPath),
            'saved' => $saved
        ]);
    }

    protected function editAsString($request)
    {
        $path = $request->getQuery('path', null);
        $absPath = $this->alias->get($path);

        $saved = false;
        if ($this->request->getMethod() == 'POST') {
            $content = $request->getPost('content', null);
            $saved = file_put_contents($absPath, $content);
        }

        return $this->render('data/editstring.twig', [
            'content' => file_get_contents($absPath),
            'saved' => $saved
        ]);
    }
}
