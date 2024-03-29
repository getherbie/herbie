<?php

declare(strict_types=1);

namespace herbie\sysplugins\twig;

use RecursiveFilterIterator;

final class PageTreeFilterIterator extends RecursiveFilterIterator
{
    private bool $enableFilter;

    public function __construct(PageTreeIterator $recursiveIterator, bool $enableFilter = true)
    {
        $this->enableFilter = $enableFilter;
        parent::__construct($recursiveIterator);
    }

    public function accept(): bool
    {
        if (!$this->enableFilter) {
            return true;
        }
        $menuItem = $this->current()->getMenuItem();
        if (empty($menuItem->getHidden())) {
            return true;
        }
        return false;
    }

    public function getChildren(): self
    {
        return new self(
            $this->getInnerIterator()->getChildren(), // @phpstan-ignore-line
            $this->enableFilter
        );
    }
}
