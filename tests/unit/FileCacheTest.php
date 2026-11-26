<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\FileCache;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

final class FileCacheTest extends Unit
{
    private string $cachePath;

    protected function _before(): void
    {
        $this->cachePath = sys_get_temp_dir() . '/herbie-cache-test-' . uniqid();
        mkdir($this->cachePath, 0777, true);
    }

    protected function _after(): void
    {
        foreach (glob($this->cachePath . '/*') as $f) {
            @unlink($f);
        }
        @rmdir($this->cachePath);
    }

    public function testEmptyPathThrowsPsrInvalidArgumentException(): void
    {
        $this->expectException(PsrInvalidArgumentException::class);
        new FileCache('');
    }

    public function testSetAndGet(): void
    {
        $cache = new FileCache($this->cachePath);
        $this->assertTrue($cache->set('key', 'value'));
        $this->assertTrue($cache->has('key'));
        $this->assertSame('value', $cache->get('key'));
    }

    public function testSetOverwritesValueWithoutLeftovers(): void
    {
        $cache = new FileCache($this->cachePath);
        $cache->set('key', 'old');
        $cache->set('key', 'new');
        $this->assertSame('new', $cache->get('key'));
        $this->assertSame(['key'], array_values(array_diff(scandir($this->cachePath), ['.', '..'])));
    }

    public function testGetDefault(): void
    {
        $cache = new FileCache($this->cachePath);
        $this->assertSame('default', $cache->get('missing', 'default'));
    }

    public function testDelete(): void
    {
        $cache = new FileCache($this->cachePath);
        $cache->set('key', 'value');
        $this->assertTrue($cache->delete('key'));
        $this->assertFalse($cache->has('key'));
    }

    public function testClear(): void
    {
        $cache = new FileCache($this->cachePath);
        $cache->set('a', 1);
        $cache->set('b', 2);
        file_put_contents($this->cachePath . '/plugins.php', '<?php return [];');
        file_put_contents($this->cachePath . '/.gitignore', "*\n!.gitignore\n");
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->has('a'));
        $this->assertFalse($cache->has('b'));
        $this->assertFileDoesNotExist($this->cachePath . '/plugins.php');
        $this->assertFileExists($this->cachePath . '/.gitignore');
        unlink($this->cachePath . '/.gitignore');
    }

    public function testUnexpectedCacheValueReturnsDefault(): void
    {
        $cache = new FileCache($this->cachePath);
        file_put_contents($this->cachePath . '/key', serialize('garbage'));
        $this->assertSame('default', $cache->get('key', 'default'));
    }

    public function testKeyIsUsedAsFilename(): void
    {
        $cache = new FileCache($this->cachePath);
        $cache->set('herbie_pages_modified', 1700000000);
        $this->assertFileExists($this->cachePath . '/herbie_pages_modified');
    }

    public function testMinimumCharacterSetIsAllowed(): void
    {
        $cache = new FileCache($this->cachePath);

        foreach (['key_1', 'a.b'] as $key) {
            $this->assertTrue($cache->set($key, 'value'));
            $this->assertSame('value', $cache->get($key));
        }
    }

    public function testTtlExpiry(): void
    {
        $cache = new FileCache($this->cachePath);
        $cache->set('key', 'value', 1);
        sleep(2);
        $this->assertNull($cache->get('key'));
    }

    public function testDateIntervalTtlExpires(): void
    {
        $cache = new FileCache($this->cachePath);
        $cache->set('key', 'value', new \DateInterval('PT1S'));
        $this->assertSame('value', $cache->get('key'));
        sleep(2);
        $this->assertNull($cache->get('key'));
    }

    public function testMultiple(): void
    {
        $cache = new FileCache($this->cachePath);
        $cache->setMultiple(['a' => 1, 'b' => 2]);
        $this->assertSame(['a' => 1, 'b' => 2], $cache->getMultiple(['a', 'b']));
        $cache->deleteMultiple(['a', 'b']);
        $this->assertFalse($cache->has('a'));
    }

    public function testIllegalKeysThrowForAllKeyMethods(): void
    {
        $cache = new FileCache($this->cachePath);
        $operations = [
            static fn(string $key): mixed => $cache->get($key),
            static fn(string $key): bool => $cache->set($key, 'value'),
            $cache->has(...),
            $cache->delete(...),
        ];

        foreach (['', '.', '..', 'page-1', 'has space', 'a/b', 'a\\b', 'a@b', 'a:b', 'a{b', 'a}b', 'a(b', 'a)b', "a\0b", str_repeat('a', 65)] as $key) {
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
        $cache = new FileCache($this->cachePath);
        $this->expectException(PsrInvalidArgumentException::class);
        $cache->getMultiple(['a/b']);
    }

    public function testSetMultipleReturnsFalseWhenOneWriteFails(): void
    {
        $cache = new FileCache($this->cachePath);
        mkdir($this->cachePath . '/key');

        set_error_handler(static fn(int $type, string $message): bool => true);
        try {
            $result = $cache->setMultiple(['key' => 'value', 'other' => 'value']);
        } finally {
            restore_error_handler();
        }

        $this->assertFalse($result);
        $this->assertSame('value', $cache->get('other'));

        rmdir($this->cachePath . '/key');
    }

    public function testDeleteMultipleReturnsFalseWhenOneDeleteFails(): void
    {
        $cache = new FileCache($this->cachePath);
        $cache->set('other', 'value');
        mkdir($this->cachePath . '/key');

        set_error_handler(static fn(int $type, string $message): bool => true);
        try {
            $result = $cache->deleteMultiple(['key', 'other']);
        } finally {
            restore_error_handler();
        }

        $this->assertFalse($result);
        $this->assertFalse($cache->has('other'));

        rmdir($this->cachePath . '/key');
    }
}
