<?php

namespace herbie\sysplugins\adminpanel\controllers;

use herbie\sysplugins\adminpanel\validators\FileNotExistsRule;
use Herbie\Yaml;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rakit\Validation\Validator;
use Symfony\Component\Filesystem\Exception\ExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class DataController extends Controller
{
    private Filesystem $fs;
    
    protected function init(): void
    {
        $this->fs = new Filesystem();
        $dir = $this->alias->get('@site/data');
        try {
            if (!$this->fs->exists($dir)) {
                throw new \Exception(sprintf('Dir "%s" not exist', $dir));
            }
            if (!is_writable($dir)) {
                throw new \Exception(sprintf('Dir "%s" not writable', $dir));
            }            
        } catch (ExceptionInterface $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    public function testAction(ServerRequestInterface $request): ResponseInterface
    {
        return $this->redirect('data/index');
    }

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $dir = $this->alias->get('@site/data/');
        $data = $this->dataRepository->loadAll();
        foreach ($data as $key => $_) {
            $path = $dir . $key . '.yml';
            $data[$key] = [
                'name' => $key,
                'size' => is_readable($path) ? filesize($path) : 0,
                'created' => filectime($path),
                'modified' => filemtime($path)
            ];
        }
        return $this->render('data/index.twig', [
            'data' => $data,
            'dir' => $dir
        ]);
    }

    public function addAction(ServerRequestInterface $request): ResponseInterface
    {
        $errors = [];
        $values = $request->getParsedBody();
        
        $validator = new Validator();
        $validator->addValidator('file_not_exists', new FileNotExistsRule($this->alias));

        $aliasedPathWithPlaceholder = $this->alias->get('@site/data/{value}.yml');
        $validation = $validator->make($values, [
            'name' => 'required|lowercase|alpha_dash|file_not_exists:' . $aliasedPathWithPlaceholder,
        ]);
        
        if ($request->getMethod() === 'POST') {
            $validation->validate();
            if ($validation->fails() || !empty($request->getHeader('X-Up-Validate'))) {
                $errors = $validation->errors()->firstOfAll();
            } else {
                $file = str_replace('{value}', $values['name'], $this->alias->get($aliasedPathWithPlaceholder));
                try {
                    $this->fs->touch($file);
                    return $this->redirect('data/index');
                } catch (ExceptionInterface $e) {
                    $errors['name'] = $this->t('File "{name}" can not be created.', ['name' => $values['name']]);
                }
            }
        }

        $status = empty($errors) ? 200 : 400;
        
        return $this->render('data/add.twig', [
            'errors' => $errors,
            'values' => $values,
        ], $status);
    }

    public function deleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getQueryParams()['path'] ?? '';
        $absPath = $this->alias->get($path);

        $errors = [];

        if ($request->getMethod() === 'POST') {
            if (!is_file($absPath)) {
                $errors['name'] = $this->t('File {file} does not exist.', ['file' => $path]);
            }
            if (!@unlink($absPath)) {
                $errors['name'] = $this->t('File {file} can not be deleted.', ['file' => $path]);
            }
            if (empty($errors)) {
                return $this->redirect('data/index');
            }
        }

        $status = empty($errors) ? 200 : 400;
        
        return $this->render('data/delete.twig', [
            'errors' => $errors,
            'path' => $path,
        ], $status);
    }

    public function editAction(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getQueryParams()['path'] ?? '';
        $absPath = $this->alias->get($path);

        // Config
        $name = pathinfo($absPath, PATHINFO_FILENAME);
        $configKey = 'plugins.adminpanel.data.' . $name . '.config';
        $config = $this->config->getAsArray($configKey);
        if (empty($config)) {
            return $this->editAsString($request);
        }

        $saved = false;
        if ($this->request->getMethod() == 'POST') {
            $data = $request->getParsedBody()['data'] ?? '';
            $content = Yaml::dump(array_values($data));
            $saved = file_put_contents($absPath, $content);
            if ($request->getParsedBody()['button'] === 'saveAndClose') {
                return $this->redirect('data/index');
            }
        }

        return $this->render('data/edit.twig', [
            'config' => $config,
            'data' => Yaml::parseFile($absPath),
            'saved' => $saved
        ]);
    }

    protected function editAsString(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getQueryParams()['path'] ?? '';
        $absPath = $this->alias->get($path);

        $saved = false;
        if ($this->request->getMethod() == 'POST') {
            $content = $request->getParsedBody()['content'] ?? '';
            $saved = file_put_contents($absPath, $content);
            if ($request->getParsedBody()['button'] === 'saveAndClose') {
                return $this->redirect('data/index');
            }            
        }

        return $this->render('data/editstring.twig', [
            'content' => file_get_contents($absPath),
            'saved' => $saved
        ]);
    }
}
