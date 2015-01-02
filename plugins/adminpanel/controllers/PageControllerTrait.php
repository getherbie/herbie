<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\adminpanel\controllers;

use Herbie\Helper\ArrayHelper;
use Herbie\Helper\PageHelper;
use Herbie\Loader\FrontMatterLoader;

trait PageControllerTrait
{

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
            if(PageHelper::updateData($path, $update)) {
                $data = $loader->load($path);
                $saved = true;
            }
        }

        return $this->render('data.twig', [
            'data' => $data,
            'fields' => $fields,
            'saved' => $saved,
            'controller' => $this->controller,
            'action' => $this->action
        ]);
    }

    public function contentAction($query, $request)
    {
        $alias = $query->get('path', null);
        $path = $this->app['alias']->get($alias);

        $saved = false;
        if(!empty($_POST)) {
            $segments = $request->get('segments', []);
            if(PageHelper::updateSegments($path, $segments)) {
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

        return $this->render('content.twig', [
            'segments' => $segments,
            'page' => $page,
            'saved' => $saved,
            'controller' => $this->controller,
            'action' => $this->action
        ]);
    }

}
