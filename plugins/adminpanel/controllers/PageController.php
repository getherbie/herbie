<?php

namespace herbie\sysplugins\adminpanel\controllers;

use Exception;
use herbie\sysplugins\adminpanel\components\Filesystem;
use herbie\sysplugins\adminpanel\validators\FileNotExistsRule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rakit\Validation\Validator;

class PageController extends Controller
{
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

        $validator = new Validator();
        $validation = $validator->make($values, [
            'parent' => 'required|nullable',
            'name' => 'required',
            'title' => 'required',
            'type' => 'required|min:2',
        ]);

        if ($request->getMethod() === 'POST') {
            $validation->validate();
            if ($validation->fails() || !empty($request->getHeader('X-Up-Validate'))) {
                $errors = $validation->errors()->firstOfAll();
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
                    $errors['common'] = $e->getMessage();
                }
            }
        }

        $status = empty($errors) ? 200 : 400;

        return $this->render('page/add.twig', [
            'types' => $this->getTypes(),
            'values' => $values,
            'errors' => $errors,
        ], $status);
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

        $iterator = $this->finder->pageFiles($dir);

        $test = [];
        foreach ($iterator as $item) {
            $test[] = $item->getRelativePath();
        }

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


    public function dataAction(ServerRequestInterface $request)
    {
        $alias = $request->getQuery('path');
        $path = $this->alias->get($alias);

        #$data = $this->getService('Loader\PageLoader')->load($alias);

        $loader = new FrontMatterLoader();
        $data = $loader->load($path);

        // Readonly
        $data['Alias'] = $alias;
        $data['path'] = $path;

        $fields = $this->config->get('plugins.config.adminpanel.fields', []);
        $unconfig = [];

        foreach ($data as $key => $value) {
            if (empty($fields[$key]['type'])) {
                $fields[$key] = [
                    'label' => ucfirst($key)
                ];
                $unconfig[$key] = $value;
            }
        }

        $saved = false;
        if (!empty($_POST)) {
            $update = array_merge($request->getPost('data', []), $unconfig);
            $update = ArrayHelper::filterEmptyElements($update);
            if (PageHelper::updateData($path, $update)) {
                $data = $loader->load($path);
                $saved = true;
            }
        }

        $groups = ['Allgemein', 'Media', 'Tags', 'Layout', 'Info'];
        $fieldsets = [];
        foreach ($fields as $key => $field) {
            $group = isset($field['group']) ? $field['group'] : '';
            $fieldset = in_array($group, $groups) ? $group : '';
            $fieldsets[$fieldset][$key] = $field;
        }
        $fieldsets = ArrayHelper::sortArrayByArray($fieldsets, $groups);

        return $this->render('data.twig', [
            'data' => $data,
            'fields' => $fields,
            'saved' => $saved,
            'controller' => $this->controller,
            'action' => $this->action,
            'cancel' => $request->getQuery('cancel'),
            'Alias' => $alias,
            'fieldsets' => $fieldsets
        ]);
    }

    public function contentAction(ServerRequestInterface $request)
    {
        $alias = $request->getQuery('path', null);
        $path = $this->alias->get($alias);

        $saved = false;
        if (!empty($_POST)) {
            $segments = $request->getPost('segments', []);
            if (PageHelper::updateSegments($path, $segments)) {
                $saved = true;
            }
        }

        $page = $this->getService('Loader\PageLoader')->load($path, false);
        $data = $page['data'];

        // Segment config
        $layouts = $this->config->get('plugins.config.adminpanel.layouts', []);
        $layout = empty($data['layout']) ? 'default.html' : $data['layout'];

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

        return $this->render('content.twig', [
            'segments' => $segments,
            'page' => $page,
            'saved' => $saved,
            'controller' => $this->controller,
            'action' => $this->action,
            'cancel' => $request->getQuery('cancel')
        ]);
    }    
}
