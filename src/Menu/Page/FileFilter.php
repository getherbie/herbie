<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Page;

class FileFilter extends \RecursiveFilterIterator
{

    public function accept()
    {
        $firstChar = substr($this->current()->getFileName(), 0, 1);
        return !in_array($firstChar, ['.', '_']);
    }

}