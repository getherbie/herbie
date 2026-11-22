<?php

declare(strict_types=1);

namespace herbie;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

final class NullCache implements CacheInterface
{
    /**
     * @param string $key
     * @param null $default
     * @return mixed
     */
    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param null $ttl
     */
    #[\Override]
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        return false;
    }

    /**
     * @param string $key
     */
    #[\Override]
    public function delete(string $key): bool
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
    public function getMultiple(iterable $keys, mixed $default = null): iterable
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
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        return false;
    }

    /**
     * @param iterable $keys
     */
    #[\Override]
    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }

    /**
     * @param string $key
     */
    #[\Override]
    public function has(string $key): bool
    {
        return false;
    }
}
