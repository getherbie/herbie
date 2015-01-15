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
        $this->parentRoutes = $app['parentRoutes'];
    }

    public function call($current, $key, $iterator)
    {
        $menuItem = $current->getMenuItem();

        $accept = true;
        if(empty($this->showHidden)) {
            $accept &= empty($menuItem->hidden);
        }
        $accept &= in_array($menuItem->getParentRoute(), $this->parentRoutes);

        return $accept;
    }

}