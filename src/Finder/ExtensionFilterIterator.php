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

class ExtensionFilterIterator extends \FilterIterator
{
    /**
     * @var array
     */
    protected $extensions;

    /**
     * @param \Iterator $iterator
     * @param array $extensions
     */
    public function __construct(\Iterator $iterator, $extensions = [])
    {
        $this->extensions = $extensions;

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept()
    {
        $fileinfo = $this->current();
        if ($fileinfo->isFile()) {
            return empty($this->extensions) || in_array($fileinfo->getExtension(), $this->extensions);
        }
        return true;
    }
}
