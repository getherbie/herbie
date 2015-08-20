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

use Herbie\Menu\ItemInterface;

trait CollectionTrait
{

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @param ItemInterface $item
     */
    public function addItem(ItemInterface $item)
    {
        $route = $item->getRoute();
        $this->items[$route] = $item;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param string $route
     * @return Item|null
     */
    public function getItem($route)
    {
        return isset($this->items[$route]) ? $this->items[$route] : null;
    }

    /**
     * @return \ArrayIterator|Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @return ItemInterface
     */
    public function getRandom()
    {
        $routes = array_keys($this->items);
        $index = mt_rand(0, $this->count()-1);
        $route = $routes[$index];
        return $this->items[$route];
    }

    /**
     * @param string $value
     * @param string $key
     * @return ItemInterface|null
     */
    public function find($value, $key)
    {
        foreach ($this->items as $item) {
            if ($item->$key == $value) {
                return $item;
            }
        }
        return null;
    }
}
