<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\NullCache;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

final class NullCacheTest extends Unit
{
    public function testGetReturnsDefaultAndSetReturnsFalse(): void
    {
        $cache = new NullCache();

        $this->assertSame('default', $cache->get('key', 'default'));
        $this->assertFalse($cache->set('key', 'value'));
    }

    public function testDeleteAndClearReturnTrue(): void
    {
        $cache = new NullCache();

        $this->assertTrue($cache->delete('key'));
        $this->assertTrue($cache->clear());
    }

    public function testMultipleMethodsReturnEmptyValues(): void
    {
        $cache = new NullCache();

        $this->assertSame(['a' => 'default'], $cache->getMultiple(['a'], 'default'));
        $this->assertFalse($cache->setMultiple(['a' => 1]));
        $this->assertTrue($cache->deleteMultiple(['a']));
    }

    public function testHyphensAreAllowed(): void
    {
        $cache = new NullCache();

        $this->assertSame('default', $cache->get('page-1', 'default'));
        $this->assertFalse($cache->set('page-1', 'value'));
    }

    public function testIllegalKeysThrowForAllKeyMethods(): void
    {
        $cache = new NullCache();
        $operations = [
            static fn(string $key) => $cache->get($key),
            static fn(string $key) => $cache->set($key, 'value'),
            static fn(string $key) => $cache->has($key),
            static fn(string $key) => $cache->delete($key),
        ];

        foreach (['', '.', '..', 'has space', 'a/b', 'a@b', 'a:b', str_repeat('a', 65)] as $key) {
            foreach ($operations as $operation) {
                try {
                    $operation($key);
                    $this->fail('Expected InvalidArgumentException for key ' . $key);
                } catch (PsrInvalidArgumentException) {
                    $this->addToAssertionCount(1);
                }
            }
        }
    }

    public function testMultipleMethodsValidateKeys(): void
    {
        $cache = new NullCache();

        $this->expectException(PsrInvalidArgumentException::class);
        $cache->getMultiple(['a/b']);
    }
}
