<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Post;

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
        $route = trim(basename($this->path), '/');
        $route = preg_replace('/^([0-9]{4})-([0-9]{2})-([0-9]{2})(.*)$/', '\\1/\\2/\\3\\4', $route);

        // Endung entfernen
        $pos = strrpos($route, '.');
        if ($pos !== false) {
            $route = substr($route, 0, $pos);
        }

        if (empty($this->blogRoute)) {
            return $route;
        }
        return $this->blogRoute . '/' . $route;
    }

    /**
     * @param string $route
     * @return bool
     */
    public function routeEquals($route)
    {
        return $this->getRoute() == $route;
    }
}
