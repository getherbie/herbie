<?php

namespace herbie\sysplugins\adminpanel\controllers;

use Exception;
use herbie\sysplugins\adminpanel\components\Filesystem;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rakit\Validation\Validator;

class PageController extends Controller
{
    use PageControllerTrait;

    public function addAction(ServerRequestInterface $request): ResponseInterface|string
    {
        $defaults = [
            'parent' => $request->getQueryParams()['parent'] ?? '',
            'name' => '',
            'title' => '',
            'type' => '',
        ];

        $values = array_merge($defaults, $request->getParsedBody());

        $errors = [];

        $validation = (new Validator())->make($values, [
            'parent' => 'required|nullable',
            'name' => 'required',
            'title' => 'required',
            'type' => 'required|min:2',
        ]);

        if ($request->getMethod() === 'POST') {
            $validation->validate();
            if ($validation->fails()) {
                $errors = $validation->errors()->firstOfAll();
                http_response_code(422);
            } else {
                $filepath = ['@page'];
                if ($values['parent'] !== '') {
                    $filepath[] = trim($values['parent'], '/');
                }
                $filepath[] = sprintf('%s.%s', $values['name'], $values['type']);
                $id = implode('/', $filepath);
                try {
                    $this->pagePersistence->add($id, [
                        'title' => $values['title'],
                        'hidden' => true
                    ]);
                    return $this->redirect('page/index&route=' . $defaults['parent']);
                } catch (Exception $e) {
                    http_response_code(422);
                    $errors['common'] = $e->getMessage();
                }
            }
        }

        return $this->render('page/add.twig', [
            'types' => $this->getTypes(),
            'values' => $values,
            'errors' => $errors,
        ]);
    }

    private function getTypes(): array
    {
        $types = [''] + explode(',', $this->config->getAsString('fileExtensions.pages'));
        return array_combine($types, $types);
    }

    public function xxxaddAction(ServerRequestInterface $request)
    {
        $title = $request->getParsedBody()['name'] ?? '';
        $parent = $request->getParsedBody()['parent'] ?? '';
        if (empty($title)) {
#            return $this->error($this->t('Name cannot be empty.'), 418);
        }

        #$parent = FilesystemHelper::sanitizeFilename($parent);
        #$filename = FilesystemHelper::sanitizeFilename($title);
        $filename = $parent;

        $parentRoute = $this->getPageTree()->findByRoute($parent);

        if ($parentRoute->isRoot()) {
            $filepath = $this->alias->get("@page/{$filename}.md");
        } else {
            $parentAlias = dirname($parentRoute->getMenuItem()->getPath());
            $filepath = $this->alias->get("{$parentAlias}/{$filename}.md");
        }

        if (is_file($filepath)) {
            $this->sendErrorHeader($this->t('A page with the same name already exists.'));
        }
        $eol = PHP_EOL;
        $pageTitle = $this->t('My new page');
        $data = "---{$eol}title: {$title}{$eol}disabled: 1{$eol}hidden: 1{$eol}---{$eol}{$pageTitle}{$eol}";
        if (!file_put_contents($filepath, $data)) {
            $this->sendErrorHeader($this->t('Page {name} can not be created.', ['{name}' => $title]));
        }

        if (!empty($parent)) {
            $request->setQuery('route', $parent);
        }
        return $this->indexAction($request);
    }

    protected function getPageTree()
    {
        return $this->pageRepository->findAll()->getPageTree();
        #$menu = $this->getService('Menu\Page\Builder')->buildCollection();
        #return Page\Node::buildTree($menu);
    }

    public function indexAction(ServerRequestInterface $request)
    {
        $route = $request->getQueryParams()['route'] ?? '';
        $dir = '@page/' . ($request->getQueryParams()['dir'] ?? '');

        $tree = $this->getPageTree()->findByRoute($route);
        $params = [
            'tree' => $tree,
            'cancel' => $route,
            'breadcrumb' => $route,
            'dir' => $this->config->get('paths.pages'),
            'parent' => $route, // for macro.grid.addblock_js()
            'files' => []
        ];
        return $this->render('page/index.twig', $params);
    }

    public function deleteAction(ServerRequestInterface $request)
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
        $tree = $this->getPageTree()->findBy('path', $file);
        $hasChildren = ($tree && $tree->hasChildren()) ? true : false;
        if ($hasChildren) {
            $this->sendErrorHeader(
                $this->t('Page {name} has sub pages and can not be deleted.', ['{name}' => $basename])
            );
        }
        if (!@unlink($filepath)) {
            $this->sendErrorHeader($this->t('Page {name} can not be deleted.', ['{name}' => $basename]));
        }
        header('Content-Type: application/json');
        echo json_encode(true);
        exit;
    }

    /*public function editAction($request)
    {
        $path = $request->getQuery('path', null);

        $data = $this->getService('Loader\PageLoader')->load($path, false);

        $absPath = $this->alias->get($path);
        $action = strpos($path, '@page') !== false ? 'page/index' : 'post/index';

        if(is_null($path)) {
            throw new \Exception('Path must be set');
        }

        $data = $request->getPost('data', file_get_contents($absPath));
        $content = $request->getPost('content', file_get_contents($absPath));

        $saved = false;
        if($this->app['request']->getMethod() == 'POST') {
            $saved = file_put_contents($absPath, $content);

            if ($request->getPost('button2') !== null) {
                $this->twig->environment
                    ->getExtension('herbie\\plugin\\twig\\classes\\HerbieExtension')
                    ->functionRedirect('adminpanel?action=' . $action);
            }

            if ($request->getPost('button3') !== null) {
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

    protected function redirectBack($path)
    {
        $item = $this->getService('Menu\Page\Collection')->find($path, 'path');
        if (is_null($item)) {
            $item = $this->getService('Menu\Post\Collection')->find($path, 'path');
        }
        if (isset($item)) {
            $route = $item->route;
        } else {
            $route = '';
        }
        $this->twig->getEnvironment()
            ->getExtension('herbie\\plugin\\twig\\classes\\HerbieExtension')
            ->functionRedirect($route);
    }
}
