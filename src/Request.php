<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request extends SymfonyRequest
{
    /**
     * Get the current route.
     * @return string
     */
    public function getRoute()
    {
        $route = $this->getRawRoute();
        return $route[0];
    }

    /**
     * Get the parts of the current route.
     * @return array
     */
    public function getRouteParts()
    {
        $route = $this->getRoute();
        return empty($route) ? [] : explode('/', $route);
    }

    /**
     * Get all routes from root to current page as an index array.
     * @return array
     */
    public function getRouteLine()
    {
        $route = '';
        $delim = '';
        $routeLine[] = ''; // root
        foreach ($this->getRouteParts() as $part) {
            $route .= $delim . $part;
            $routeLine[] = $route;
            $delim = '/';
        }
        return $routeLine;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        $route = $this->getRawRoute();
        return $route[1];
    }

    /**
     * @return array
     */
    private function getRawRoute()
    {
        $pathInfo = trim($this->getPathInfo(), '/');
        $pos = strrpos($pathInfo, ':');
        if ($pos !== false) {
            $parts = [substr($pathInfo, 0, $pos), substr($pathInfo, $pos + 1)];
        } else {
            $parts = [$pathInfo, ''];
        }
        return array_map('trim', $parts);
    }
}
