<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

class PageTreeIterator implements \RecursiveIterator
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
     * @param PageTree|array $context
     */
    public function __construct($context)
    {
        if ($context instanceof PageTree) {
            $this->children = $context->getChildren();
        } elseif (is_array($context)) {
            $this->children = $context;
        }
    }

    /**
     * @return PageTreeIterator
     */
    public function getChildren()
    {
        return new self($this->children[$this->position]->getChildren());
    }

    /**
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->children[$this->position]->hasChildren();
    }

    /**
     * @return PageTree
     */
    public function current(): PageTree
    {
        return $this->children[$this->position];
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $this->position++;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->children[$this->position]);
    }

    /**
     * @return PageItem
     */
    public function getMenuItem(): PageItem
    {
        return $this->children[$this->position]->getMenuItem();
    }
}
