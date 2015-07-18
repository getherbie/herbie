<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <http://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request extends SymfonyRequest
{
    /**
     * @return string
     */
    public function getRoute()
    {
        $route = $this->getRawRoute();
        return $route[0];
    }

    /**
     * @return array
     */
    public function getRouteSegments()
    {
        $route = $this->getRoute();
        return empty($route) ? [] : explode('/', $route);
    }

    /**
     * @return array
     */
    public function getParentRoutes()
    {
        $route = '';
        $delim = '';
        $parentRoutes[] = ''; // root
        foreach ($this->getRouteSegments() as $segment) {
            $route .= $delim . $segment;
            $parentRoutes[] = $route;
            $delim = '/';
        }
        return $parentRoutes;
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
