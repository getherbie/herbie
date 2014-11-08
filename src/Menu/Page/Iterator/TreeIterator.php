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

class TreeIterator implements \RecursiveIterator
{
    /**
     * @var array
     */
    private $children = [];

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @param mixed $context
     */
    public function __construct($context)
    {
        if (is_object($context)) {
            $this->children = $context->getChildren();
        } elseif (is_array($context)) {
            $this->children = $context;
        }
    }

    /**
     * @return \self
     */
    public function getChildren()
    {
        return new self($this->children[$this->position]->getChildren());
    }

    /**
     * @return boolean
     */
    public function hasChildren()
    {
        return $this->children[$this->position]->hasChildren();
    }

    /**
     * @return Herbie\Menu\Page\Node
     */
    public function current()
    {
        return $this->children[$this->position];
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return boolean
     */
    public function valid()
    {
        return isset($this->children[$this->position]);
    }

    /**
     * @return Item
     */
    public function getMenuItem()
    {
        return $this->children[$this->position]->getMenuItem();
    }
}
