<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\FileCache;

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

    public function testSetAndGet(): void
    {
        $cache = new FileCache($this->cachePath);
        $this->assertTrue($cache->set('key', 'value'));
        $this->assertTrue($cache->has('key'));
        $this->assertSame('value', $cache->get('key'));
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
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->has('a'));
        $this->assertFalse($cache->has('b'));
    }

    public function testTtlExpiry(): void
    {
        $cache = new FileCache($this->cachePath);
        $cache->set('key', 'value', 1);
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
}
