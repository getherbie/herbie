<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Page\Iterator;

class FilterIterator extends \RecursiveFilterIterator
{
    /**
     * @var boolean
     */
    private $enabled = true;

    /**
     * @return boolean
     */
    public function accept()
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
     * @param boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;
    }
}
