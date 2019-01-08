<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie\Menu;

class MenuTrail implements \IteratorAggregate, \Countable
{

    /**
     * @var MenuList
     */
    private $menuList;

    /**
     * @var string
     */
    private $route;

    /**
     * @var array
     */
    private $items;

    /**
     * @param MenuList $menuList
     * @param string $route
     */
    public function __construct(MenuList $menuList, string $route)
    {
        $this->menuList = $menuList;
        $this->route = $route;
        $this->items = $this->buildMenuTrail();
    }

    /**
     * @return array
     */
    private function buildMenuTrail(): array
    {
        $items = [];

        $segments = explode('/', rtrim($this->route, '/'));
        $route = '';
        $delim = '';
        foreach ($segments as $segment) {
            $route .= $delim . $segment;
            $delim = '/';

            $item = $this->menuList->getItem($route);
            if (isset($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }
}
