<?php

declare(strict_types=1);

namespace herbie\finder;

class DepthRangeFilterIterator extends \FilterIterator
{
    private int $minDepth;

    public function __construct(\RecursiveIteratorIterator $iterator, int $minDepth = 0, int $maxDepth = \PHP_INT_MAX)
    {
        $this->minDepth = $minDepth;
        $iterator->setMaxDepth(\PHP_INT_MAX === $maxDepth ? -1 : $maxDepth);

        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        return $this->getInnerIterator()->getDepth() >= $this->minDepth;
    }
}
