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

namespace Herbie\Menu\Iterator;

use Herbie\Menu\MenuTree;

class FilterCallback
{

    /**
     * @var array
     */
    protected $routeLine;

    public function __construct(array $routeLine)
    {
        $this->routeLine = $routeLine;
    }

    public function call(MenuTree $current)
    {
        $menuItem = $current->getMenuItem();

        $accept = true;
        if (empty($this->showHidden)) {
            $accept &= empty($menuItem->hidden);
        }
        $accept &= in_array($menuItem->getParentRoute(), $this->routeLine);

        return $accept;
    }
}
