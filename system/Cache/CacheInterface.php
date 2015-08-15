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

/**
 * CacheInterface is the interface that must be implemented by cache classes.
 */
interface CacheInterface
{
    /**
     * Retrieves a value from cache with a specified key.
     * @param string $id A key identifying the cached value.
     */
    public function get($id);

    /**
     * Stores a value identified by a key into cache.
     * @param string $id The key identifying the value to be cached.
     * @param mixed $value The value to be cached.
     */
    public function set($id, $value);
}
