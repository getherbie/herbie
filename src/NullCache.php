<?php

declare(strict_types=1);

namespace herbie;

use Psr\SimpleCache\CacheInterface;

final class NullCache implements CacheInterface
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
     */
    public function set($key, $value, $ttl = null): bool
    {
        return false;
    }

    /**
     * @param string $key
     */
    public function delete($key): bool
    {
        return true;
    }

    public function clear(): bool
    {
        return true;
    }

    /**
     * @param iterable $keys
     * @param null $default
     * @return iterable
     */
    public function getMultiple($keys, $default = null)
    {
        foreach ($keys as $key) {
            yield $key => $default;
        }
    }

    /**
     * @param iterable $values
     * @param null $ttl
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return false;
    }

    /**
     * @param iterable $keys
     */
    public function deleteMultiple($keys): bool
    {
        return true;
    }

    /**
     * @param string $key
     */
    public function has($key): bool
    {
        return false;
    }
}
