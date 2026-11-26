<?php

declare(strict_types=1);

namespace herbie\plugins\twig;

use herbie\Page;
use herbie\PageTree;
use RecursiveIterator;

/** @implements \RecursiveIterator<mixed, mixed> */
final class PageTreeIterator implements RecursiveIterator
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

    #[\Override]
    public function getChildren(): PageTreeIterator
    {
        return new self($this->children[$this->position]->getChildren());
    }

    #[\Override]
    public function hasChildren(): bool
    {
        return $this->children[$this->position]->hasChildren();
    }

    #[\Override]
    public function current(): PageTree
    {
        return $this->children[$this->position];
    }

    #[\Override]
    public function key(): int
    {
        return $this->position;
    }

    #[\Override]
    public function next(): void
    {
        $this->position++;
    }

    #[\Override]
    public function rewind(): void
    {
        $this->position = 0;
    }

    #[\Override]
    public function valid(): bool
    {
        return isset($this->children[$this->position]);
    }

    public function getMenuItem(): Page
    {
        return $this->children[$this->position]->getMenuItem();
    }
}
