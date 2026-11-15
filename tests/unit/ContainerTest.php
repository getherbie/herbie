<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Container;
use Psr\Container\ContainerInterface;
use Psr\Log\InvalidArgumentException;
use stdClass;

final class ContainerTest extends Unit
{
    public function testValueIsReturned(): void
    {
        $container = new Container();
        $container->set('key', 'value');

        $this->assertSame('value', $container->get('key'));
    }

    public function testCallableIsResolved(): void
    {
        $container = new Container();
        $container->set('key', static fn(): string => 'resolved');

        $this->assertSame('resolved', $container->get('key'));
    }

    public function testCallableReceivesTheContainer(): void
    {
        $container = new Container();
        $container->set('other', 'value');
        $container->set('key', static fn(ContainerInterface $c): string => $c->get('other') . '-resolved');

        $this->assertSame('value-resolved', $container->get('key'));
    }

    public function testCallableIsResolvedOnlyOnce(): void
    {
        $calls = 0;
        $container = new Container();
        $container->set('key', static function () use (&$calls): stdClass {
            $calls++;
            return new stdClass();
        });

        $first = $container->get('key');
        $second = $container->get('key');

        $this->assertSame(1, $calls);
        $this->assertSame($first, $second);
    }

    public function testHas(): void
    {
        $container = new Container();
        $container->set('key', 'value');

        $this->assertTrue($container->has('key'));
        $this->assertFalse($container->has('not-existing'));
    }

    public function testHasIsTrueForResolvedValues(): void
    {
        $container = new Container();
        $container->set('key', static fn(): string => 'value');
        $container->get('key');

        $this->assertTrue($container->has('key'));
    }

    public function testMissingKeyThrows(): void
    {
        $container = new Container();

        $this->expectException(InvalidArgumentException::class);
        $container->get('not-existing');
    }

    public function testSetOverwritesValue(): void
    {
        $container = new Container();
        $container->set('key', 'first');
        $container->set('key', 'second');

        $this->assertSame('second', $container->get('key'));
    }
}
