<?php

declare(strict_types=1);

namespace herbie\plugins\twig;

use RecursiveFilterIterator;

final class PageTreeFilterIterator extends RecursiveFilterIterator
{
    public function __construct(PageTreeIterator $recursiveIterator, private readonly bool $enableFilter = true)
    {
        parent::__construct($recursiveIterator);
    }

    #[\Override]
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

    #[\Override]
    public function getChildren(): self
    {
        return new self(
            $this->getInnerIterator()->getChildren(), // @phpstan-ignore-line
            $this->enableFilter,
        );
    }
}
