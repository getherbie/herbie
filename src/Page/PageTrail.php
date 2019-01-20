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

namespace Herbie\Page;

use Herbie\Environment;

class PageTrail implements \IteratorAggregate, \Countable
{

    /**
     * @var PageList
     */
    private $pageList;

    /**
     * @var string
     */
    private $route;

    /**
     * @var array
     */
    private $items;

    /**
     * @param PageList $pageList
     * @param Environment $environment
     */
    public function __construct(PageList $pageList, Environment $environment)
    {
        $this->pageList = $pageList;
        $this->route = $environment->getRoute();
        $this->items = $this->buildPageTrail();
    }

    /**
     * @return array
     */
    private function buildPageTrail(): array
    {
        $items = [];

        $segments = explode('/', rtrim($this->route, '/'));
        $route = '';
        $delim = '';
        foreach ($segments as $segment) {
            $route .= $delim . $segment;
            $delim = '/';

            $item = $this->pageList->getItem($route);
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
