<?php

namespace Herbie\Menu;

use Herbie\Menu\MenuCollection;


class MenuTreeBuilder
{

    /**
     * @var MenuCollection
     */
    protected $collection;

    /**
     * @param MenuCollection $collection
     */
    public function __construct(MenuCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @return \Herbie\Menu\MenuTree
     */
    public function build() {

        $flat = $this->collection->getItems();

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