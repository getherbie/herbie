<?php

namespace herbie\plugin\adminpanel\controllers;

class PageController extends Controller
{
    use PageControllerTrait;

    public function indexAction()
    {
        return $this->render('page/index.twig', []);
    }

    protected function redirectBack($path)
    {
        $item = $this->app['menu']->find($path, 'path');
        if(is_null($item)) {
            $item = $this->app['posts']->find($path, 'path');
        }
        if(isset($item)) {
            $route = $item->route;
        } else {
            $route = '';
        }
        $this->app['twig']->environment->getExtension('herbie')->functionRedirect($route);
    }

    /*public function editAction($query, $request)
    {
        $path = $query->get('path', null);

        $data = $this->app['pageLoader']->load($path, false);

        $absPath = $this->app['alias']->get($path);
        $action = strpos($path, '@page') !== false ? 'page/index' : 'post/index';

        if(is_null($path)) {
            throw new \Exception('Path must be set');
        }

        $data = $request->get('data', file_get_contents($absPath));
        $content = $request->get('content', file_get_contents($absPath));

        $saved = false;
        if($this->app['request']->getMethod() == 'POST') {
            $saved = file_put_contents($absPath, $content);

            if ($request->get('button2') !== null) {
                $this->app['twig']->environment->getExtension('herbie')->functionRedirect('adminpanel?action=' . $action);
            }

            if ($request->get('button3') !== null) {
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

}