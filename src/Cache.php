<?php

declare(strict_types=1);

namespace Herbie;

use Psr\SimpleCache\CacheInterface;

class Cache implements CacheInterface
{
    /**
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param null $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function clear()
    {
        return true;
    }

    /**
     * @param iterable $keys
     * @param null $default
     * @return array|iterable
     */
    public function getMultiple($keys, $default = null)
    {
        return [];
    }

    /**
     * @param iterable $values
     * @param null $ttl
     * @return bool
     */
    public function setMultiple($values, $ttl = null)
    {
        return true;
    }

    /**
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple($keys)
    {
        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return false;
    }
}
