<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Iterator;

class DirectoryDotFilter extends \FilterIterator
{

    public function accept()
    {
        return !in_array($this->getBasename(), ['.', '..']);
    }
}
