<?php

declare(strict_types=1);

namespace herbie;

final class PageTreeFilterCallback
{
    private array $routeLine;

    /**
     * FilterCallback constructor.
     */
    public function __construct(array $routeLine)
    {
        $this->routeLine = $routeLine;
    }

    public function __invoke(PageTree $current): int
    {
        $menuItem = $current->getMenuItem();

        $accept = true;
        if (empty($this->showHidden)) {
            $accept &= empty($menuItem->hidden);
        }
        $accept &= in_array($menuItem->getParentRoute(), $this->routeLine);

        return $accept ? 1 : 0;
    }
}
