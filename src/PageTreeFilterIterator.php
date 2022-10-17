<?php

declare(strict_types=1);

namespace herbie;

use RecursiveIterator;

final class PageTreeFilterIterator extends \RecursiveFilterIterator
{
    private bool $enableFilter;

    public function __construct(RecursiveIterator $recursiveIterator, bool $enableFilter = true)
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
        $route = $menuItem->route;
        if (empty($menuItem->hidden)) {
            return true;
        }
        return false;
    }

    public function getChildren(): PageTreeFilterIterator
    {
        return new self(
            $this->getInnerIterator()->getChildren(), // @phpstan-ignore-line
            $this->enableFilter
        );
    }
}
