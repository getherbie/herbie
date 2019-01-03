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

class FilterIterator extends \RecursiveFilterIterator
{
    /**
     * @var boolean
     */
    protected $enabled = true;

    /**
     * @return boolean
     */
    public function accept(): bool
    {
        if (!$this->enabled) {
            return true;
        }
        $menuItem = $this->current()->getMenuItem();
        if (empty($menuItem->hidden)) {
            return true;
        }
        return false;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = (bool)$enabled;
    }
}
