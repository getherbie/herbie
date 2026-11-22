<?php

declare(strict_types=1);

namespace herbie;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

final readonly class Cache implements CacheInterface
{
    private const string MISS = "\0herbie.versioned-cache.miss";

    public function __construct(private CacheInterface $storage) {}

    /**
     * @template T
     * @param callable(): T $producer
     * @return T
     */
    public function remember(
        string $baseKey,
        string|int $version,
        callable $producer,
        int|DateInterval|null $ttl = null,
        bool $enabled = true,
    ): mixed {
        if (!$enabled) {
            return $producer();
        }

        $key = $baseKey . '_' . $version;
        $cached = $this->storage->get($key, self::MISS);
        if ($cached !== self::MISS) {
            /** @var T $cached */
            return $cached;
        }

        $value = $producer();

        $previous = $this->storage->get($baseKey . '_previous');
        if (is_string($previous) && $previous !== $key) {
            $this->storage->delete($previous);
        }
        $this->storage->set($baseKey . '_previous', $key, $ttl);
        $this->storage->set($key, $value, $ttl);

        return $value;
    }

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->storage->get($key, $default);
    }

    #[\Override]
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        return $this->storage->set($key, $value, $ttl);
    }

    #[\Override]
    public function delete(string $key): bool
    {
        return $this->storage->delete($key);
    }

    #[\Override]
    public function clear(): bool
    {
        return $this->storage->clear();
    }

    #[\Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->storage->getMultiple($keys, $default);
    }

    #[\Override]
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        return $this->storage->setMultiple($values, $ttl);
    }

    #[\Override]
    public function deleteMultiple(iterable $keys): bool
    {
        return $this->storage->deleteMultiple($keys);
    }

    #[\Override]
    public function has(string $key): bool
    {
        return $this->storage->has($key);
    }
}
