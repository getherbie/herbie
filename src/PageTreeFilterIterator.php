<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

final class PageTreeFilterIterator extends \RecursiveFilterIterator
{
    private bool $enabled = true;

    public function accept(): bool
    {
        if (!$this->enabled) {
            return true;
        }
        $menuItem = $this->current()->getMenuItem();
        if (empty($menuItem->hidden)) {
            return true;
        }
        return false;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
