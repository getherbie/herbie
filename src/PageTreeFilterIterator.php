<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
            $this->getInnerIterator()->getChildren(),
            $this->enableFilter
        );
    }
}
