<?php

declare(strict_types=1);

namespace herbie;

use RecursiveIterator;

final class MenuTreeIterator implements RecursiveIterator
{
    private array $children = [];

    private int $position = 0;

    /**
     * @param MenuTree|array $context
     */
    public function __construct($context)
    {
        if ($context instanceof MenuTree) {
            $this->children = $context->getChildren();
        } elseif (is_array($context)) {
            $this->children = $context;
        }
    }

    public function getChildren(): MenuTreeIterator
    {
        return new self($this->children[$this->position]->getChildren());
    }

    public function hasChildren(): bool
    {
        return $this->children[$this->position]->hasChildren();
    }

    public function current(): MenuTree
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

    public function getMenuItem(): MenuItem
    {
        return $this->children[$this->position]->getMenuItem();
    }
}
