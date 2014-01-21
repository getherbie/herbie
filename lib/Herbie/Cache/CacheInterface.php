<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Cache;

interface CacheInterface
{

    /**
     * @param string $id
     */
    public function get($id);

    /**
     * @param string $id
     * @param mixed $value
     */
    public function set($id, $value);

}