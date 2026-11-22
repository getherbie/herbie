<?php

declare(strict_types=1);

namespace herbie;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

final class NullCache implements CacheInterface
{
    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        return $default;
    }

    #[\Override]
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $this->validateKey($key);

        return false;
    }

    #[\Override]
    public function delete(string $key): bool
    {
        $this->validateKey($key);

        return true;
    }

    #[\Override]
    public function clear(): bool
    {
        return true;
    }

    #[\Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $multiple = [];
        foreach ($this->validateKeys($keys) as $key) {
            $multiple[$key] = $default;
        }

        return $multiple;
    }

    #[\Override]
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->validateKey($key);
        }

        return false;
    }

    #[\Override]
    public function deleteMultiple(iterable $keys): bool
    {
        $this->validateKeys($keys);

        return true;
    }

    #[\Override]
    public function has(string $key): bool
    {
        $this->validateKey($key);

        return false;
    }

    private function validateKey(mixed $key): string
    {
        if (!is_string($key)) {
            throw new CacheInvalidArgumentException('Cache key must be a string.');
        }

        $length = strlen($key);
        if ($length < 1 || $length > 64) {
            throw new CacheInvalidArgumentException('Cache key must be 1 to 64 bytes long.');
        }

        if (strpbrk($key, '{}()/\\@:') !== false || str_contains($key, "\0") || $key === '.' || $key === '..') {
            throw new CacheInvalidArgumentException(
                'Cache key must not contain {}()/\\@: or NUL, and must not be "." or "..".',
            );
        }

        return $key;
    }

    /**
     * @return string[]
     */
    private function validateKeys(mixed $keys): array
    {
        if (!is_array($keys) && !$keys instanceof \Traversable) {
            throw new CacheInvalidArgumentException('Cache keys must be an array or Traversable.');
        }

        $result = [];
        foreach ($keys as $key) {
            $result[] = $this->validateKey($key);
        }

        return $result;
    }
}
