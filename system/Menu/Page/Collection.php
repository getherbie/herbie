<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Page;

use Herbie\Menu\CollectionTrait;

class Collection implements \IteratorAggregate, \Countable
{

    use CollectionTrait;

    public $fromCache;

}
