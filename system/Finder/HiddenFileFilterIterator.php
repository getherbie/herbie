<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Finder;

class HiddenFileFilterIterator extends \FilterIterator
{
    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept()
    {
        $fileinfo = $this->current();
        if ($fileinfo->isDir()) {
            return true;
        }
        return substr($fileinfo->getBasename(), 0, 1) !== '.';
    }
}
