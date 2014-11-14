<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Page;

use Herbie\Menu\ItemInterface;
use Herbie\Menu\ItemTrait;

class Item implements ItemInterface
{

    use ItemTrait;

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function getParentRoute()
    {
        return trim(dirname($this->route), '.');
    }

    /**
     * @return bool
     */
    public function isStartPage()
    {
        return trim($this->route) == '';
    }

    /**
     * @param string $route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * @param string $route
     * @return bool
     */
    public function routeEquals($route)
    {
        return $this->route == $route;
    }

    /**
     * @param string $route
     * @return bool
     */
    public function routeInRootPath($route)
    {
        if (empty($route) || empty($this->route)) {
            return false;
        }
        return 0 === strpos($route, $this->route);
    }
}
