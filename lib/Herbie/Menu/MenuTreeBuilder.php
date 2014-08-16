<?php
/**
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
    public function build($collection)
    {
        $flat = [];
        foreach ($collection->getItems() as $key => $item) {
            $flat[$key] = clone $item;
            $flat[$key]->items = [];
        }

        // @see http://www.tommylacroix.com/2008/09/10/php-design-pattern-building-a-tree/
        $tree = array();
        foreach ($flat as $id => &$node) {
            if (empty($node->parentRoute)) { // root node
                $tree[$id] = $node;
            } else { // sub node
                if (!isset($flat[$node->parentRoute]->items)) {
                    $flat[$node->parentRoute]->items = array();
                }
                $flat[$node->parentRoute]->items[$id] = $node;
            }
        }

        return new MenuTree($tree);
    }
}
