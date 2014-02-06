<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu;

use Herbie\Menu\MenuCollection;
use Herbie\Menu\MenuTree;

class MenuTreeBuilder
{
    /**
     * @param MenuCollection $collection
     * @return MenuTree
     */
    public function build($collection) {

        $flat = $collection->getItems();

        $root = '';
        foreach ($flat as $id => $row) {
            $flat[$row->parentRoute]->items[$id] =& $flat[$id];
            if (!$row->parentRoute) {
                $root = $id;
            }
        }

        return new MenuTree($flat['']->items);
    }

}