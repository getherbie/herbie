<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\sysplugins\adminpanel\classes;

class DirectoryDotFilter extends \FilterIterator
{

    public function accept()
    {
        return !in_array($this->getBasename(), ['.', '..']);
    }
}
