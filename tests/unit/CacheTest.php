<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use DateInterval;
use herbie\Cache;
use LogicException;
use Psr\SimpleCache\CacheInterface;

final class CacheTest extends Unit
{
    public function testHitReturnsCachedValueWithoutCallingProducer(): void
    {
        $storage = new CacheTestStorage();
        $storage->values['page_1'] = 'cached';
        $cache = new Cache($storage);

        $actual = $cache->remember('page', 1, static function (): string {
            throw new LogicException('Producer must not run on a cache hit.');
        });

        $this->assertSame('cached', $actual);
    }

    public function testMissCallsProducerAndStoresVersionedKey(): void
    {
        $storage = new CacheTestStorage();
        $cache = new Cache($storage);

        $actual = $cache->remember('page', 1, static fn(): string => 'rendered', 3600);

        $this->assertSame('rendered', $actual);
        $this->assertSame('rendered', $storage->values['page_1']);
        $this->assertSame('page_1', $storage->values['page_previous']);
        $this->assertSame(3600, $storage->ttls['page_1']);
    }

    public function testVersionChangeDeletesPreviousKey(): void
    {
        $storage = new CacheTestStorage();
        $cache = new Cache($storage);
        $cache->remember('page', 1, static fn(): string => 'v1');
        $cache->remember('page', 2, static fn(): string => 'v2');

        $this->assertArrayNotHasKey('page_1', $storage->values);
        $this->assertSame('v2', $storage->values['page_2']);
        $this->assertSame(['page_1'], $storage->deleted);
    }

    public function testDisabledSkipsStorageAndCallsProducer(): void
    {
        $storage = new CacheTestStorage();
        $cache = new Cache($storage);

        $actual = $cache->remember('page', 1, static fn(): string => 'fresh', 3600, false);

        $this->assertSame('fresh', $actual);
        $this->assertSame([], $storage->values);
    }

    public function testDelegatesPsr16Methods(): void
    {
        $storage = new CacheTestStorage();
        $cache = new Cache($storage);

        $cache->set('key', 'value', 60);
        $this->assertSame('value', $cache->get('key'));
        $this->assertTrue($cache->has('key'));
        $this->assertTrue($cache->delete('key'));
        $this->assertFalse($cache->has('key'));
    }
}

final class CacheTestStorage implements CacheInterface
{
    /** @var array<string, mixed> */
    public array $values = [];
    /** @var array<string, null|int|DateInterval> */
    public array $ttls = [];
    /** @var string[] */
    public array $deleted = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $this->values[$key] = $value;
        $this->ttls[$key] = $ttl;
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->values[$key]);
        $this->deleted[] = $key;
        return true;
    }

    public function clear(): bool
    {
        $this->values = [];
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }
}
