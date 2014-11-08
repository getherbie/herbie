<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Page\Iterator;

class FilterCallback
{

    private $parentRoutes;

    public function __construct($app)
    {

        // ParentRoutes erstellen
        $parts = empty($app['route']) ? [] : explode('/', $app['route']);
        $route = '';
        $delim = '';
        $parentRoutes[] = ''; // root
        foreach($parts as $part) {
            $route .= $delim . $part;
            $parentRoutes[] = $route;
            $delim = '/';
        }

        $this->parentRoutes = $parentRoutes;
    }

    public function call($current, $key, $iterator)
    {
        $menuItem = $current->getMenuItem();

        $accept = true;
        $accept &= in_array($menuItem->getParentRoute(), $this->parentRoutes);

        return $accept;
    }

}