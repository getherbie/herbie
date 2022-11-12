<?php

namespace tests\unit;

use herbie\Alias;

final class AliasTest extends \Codeception\Test\Unit
{
    /**
     * @var Alias
     */
    private $alias;

    protected function _before()
    {
        $this->alias = new Alias([
            '@foo' => 'foo',
            '@bar' => 'bar',
            '@baz' => 'baz'
        ]);
    }

    public function testGetAll()
    {
        $data = [
            '@foo' => 'foo',
            '@bar' => 'bar',
            '@baz' => 'baz'
        ];
        $alias = new Alias($data);
        $this->assertEquals($data, $alias->getAll());
    }

    public function testSetAlias()
    {
        $this->alias->set('@new', 'new');
        $this->assertSame('new', $this->alias->get('@new'));
    }

    public function testSetAliasWithoutAtSign()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->alias->set('my', 'value');
    }

    public function testSetEmpytAlias()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->alias->set('@', 'value');
    }

    public function testSetAliasWithMultipleAtChars()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->alias->set('@one@two', 'value');
    }

    public function testSetExistingAlias()
    {
        $this->expectException(\Exception::class);
        $this->alias->set('@foo', 'foo');
    }

    public function testGetConstructorInjectedAliases()
    {
        $this->assertSame('foo', $this->alias->get('@foo'));
        $this->assertSame('bar', $this->alias->get('@bar'));
        $this->assertSame('baz', $this->alias->get('@baz'));
    }

    public function testGetWithLongAliases()
    {
        $this->assertSame('foo/path', $this->alias->get('@foo/path'));
        $this->assertSame('foo/path/subpath', $this->alias->get('@foo/path/subpath'));
    }

    public function testUpdate()
    {
        $this->alias->update('@foo', 'fooooo');
        $this->assertSame('fooooo', $this->alias->get('@foo'));
    }

    public function testUpdateWithNonExistingAlias()
    {
        $this->expectException(\Exception::class);
        $this->alias->update('@missing', 'test');
    }
}
