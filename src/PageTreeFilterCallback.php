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

namespace Herbie;

class PageTreeFilterCallback
{

    /**
     * @var array
     */
    private $routeLine;

    /**
     * FilterCallback constructor.
     * @param array $routeLine
     */
    public function __construct(array $routeLine)
    {
        $this->routeLine = $routeLine;
    }

    /**
     * @param PageTree $current
     * @return int
     */
    public function call(PageTree $current): int
    {
        $menuItem = $current->getMenuItem();

        $accept = true;
        if (empty($this->showHidden)) {
            $accept &= empty($menuItem->hidden);
        }
        $accept &= in_array($menuItem->getParentRoute(), $this->routeLine);

        return $accept ? 1 : 0;
    }
}
