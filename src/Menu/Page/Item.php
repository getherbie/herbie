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
     * @param array $data
     * @throws \LogicException
     */
    public function setData(array $data)
    {
        if (array_key_exists('data', $data)) {
            throw new \LogicException("Field data is not allowed.");
        }
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return trim($this->data['route']);
    }

    /**
     * @return string
     */
    public function getParentRoute()
    {
        return trim(dirname($this->getRoute()), '.');
    }

    /**
     * @return bool
     */
    public function isStartPage()
    {
        return $this->getRoute() == '';
    }

    /**
     * @param string $route
     * @return bool
     */
    public function routeEquals($route)
    {
        return $this->getRoute() == $route;
    }

    /**
     * @param string $route
     * @return bool
     */
    public function routeInRootPath($route)
    {
        $current = $this->getRoute();
        if (empty($route) || empty($current)) {
            return false;
        }
        return 0 === strpos($route, $current);
    }

    /**
     * @return bool
     */
    public function isStaticPage()
    {
        return 0 === strpos($this->path, '@page');
    }
}
