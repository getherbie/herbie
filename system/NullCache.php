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
    #[\Override]
    public function get($key, $default = null)
    {
        return $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param null $ttl
     */
    #[\Override]
    public function set($key, $value, $ttl = null): bool
    {
        return false;
    }

    /**
     * @param string $key
     */
    #[\Override]
    public function delete($key): bool
    {
        return true;
    }

    #[\Override]
    public function clear(): bool
    {
        return true;
    }

    /**
     * @param iterable $keys
     * @param null $default
     * @return iterable
     */
    #[\Override]
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
    #[\Override]
    public function setMultiple($values, $ttl = null): bool
    {
        return false;
    }

    /**
     * @param iterable $keys
     */
    #[\Override]
    public function deleteMultiple($keys): bool
    {
        return true;
    }

    /**
     * @param string $key
     */
    #[\Override]
    public function has($key): bool
    {
        return false;
    }
}
