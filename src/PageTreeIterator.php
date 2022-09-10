<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

final class PageTreeIterator implements \RecursiveIterator
{
    private array $children = [];

    private int $position = 0;

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

    public function getChildren(): PageTreeIterator
    {
        return new self($this->children[$this->position]->getChildren());
    }

    public function hasChildren(): bool
    {
        return $this->children[$this->position]->hasChildren();
    }

    public function current(): PageTree
    {
        return $this->children[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->children[$this->position]);
    }

    public function getMenuItem(): PageItem
    {
        return $this->children[$this->position]->getMenuItem();
    }
}
