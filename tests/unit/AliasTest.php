<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use Exception;
use herbie\Alias;
use InvalidArgumentException;

final class AliasTest extends Unit
{
    /**
     * @var Alias
     */
    private $alias;

    public function testGetAll(): void
    {
        $data = [
            '@foo' => 'foo',
            '@bar' => 'bar',
            '@baz' => 'baz',
        ];
        $alias = new Alias($data);
        $this->assertEquals($data, $alias->getAll());
    }

    public function testSetAlias(): void
    {
        $this->alias->set('@new', 'new');
        $this->assertSame('new', $this->alias->get('@new'));
    }

    public function testSetAliasWithoutAtSign(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->alias->set('my', 'value');
    }

    public function testSetEmpytAlias(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->alias->set('@', 'value');
    }

    public function testSetAliasWithMultipleAtChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->alias->set('@one@two', 'value');
    }

    public function testSetExistingAlias(): void
    {
        $this->expectException(Exception::class);
        $this->alias->set('@foo', 'foo');
    }

    public function testGetConstructorInjectedAliases(): void
    {
        $this->assertSame('foo', $this->alias->get('@foo'));
        $this->assertSame('bar', $this->alias->get('@bar'));
        $this->assertSame('baz', $this->alias->get('@baz'));
    }

    public function testGetWithLongAliases(): void
    {
        $this->assertSame('foo/path', $this->alias->get('@foo/path'));
        $this->assertSame('foo/path/subpath', $this->alias->get('@foo/path/subpath'));
    }

    public function testGetReturnsPathWithoutAliasUnchanged(): void
    {
        $this->assertSame('/absolute/path.md', $this->alias->get('/absolute/path.md'));
        $this->assertSame('relative/path.md', $this->alias->get('relative/path.md'));
    }

    public function testGetDoesNotReplaceAnAliasInTheMiddle(): void
    {
        // only the leading alias is replaced
        $this->assertSame('mail@to.de', $this->alias->get('mail@to.de'));
        $this->assertSame('/path/@2x/image.png', $this->alias->get('/path/@2x/image.png'));
    }

    public function testGetOnlyMatchesWholeSegments(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->alias->get('@fooother/path');
    }

    public function testGetWithUnknownAliasThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Alias @missing does not exist.');
        $this->alias->get('@missing/path');
    }

    public function testGetOnlyUnknownAliasThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->alias->get('@missing');
    }

    public function testUpdate(): void
    {
        $this->alias->update('@foo', 'fooooo');
        $this->assertSame('fooooo', $this->alias->get('@foo'));
    }

    public function testUpdateWithNonExistingAlias(): void
    {
        $this->expectException(Exception::class);
        $this->alias->update('@missing', 'test');
    }

    protected function _before()
    {
        $this->alias = new Alias([
            '@foo' => 'foo',
            '@bar' => 'bar',
            '@baz' => 'baz',
        ]);
    }
}
