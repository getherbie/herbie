<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
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

    public function dataAction($request)
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

    public function contentAction($request)
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
