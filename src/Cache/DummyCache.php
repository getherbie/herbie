<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Cache;

class DummyCache implements CacheInterface
{
    /**
     * @param string $id
     * @return boolean
     */
    public function get($id)
    {
        return false;
    }

    /**
     * @param string $id
     * @param mixed $value
     * @return boolean
     */
    public function set($id, $value)
    {
        return false;
    }
}
