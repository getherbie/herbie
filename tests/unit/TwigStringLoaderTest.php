<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\TwigStringLoader;
use Twig\Error\LoaderError;

final class TwigStringLoaderTest extends Unit
{
    public function testGetSourceContextReturnsTheStringAsTemplate(): void
    {
        $loader = new TwigStringLoader();

        $source = $loader->getSourceContext('{{ page.title }}');

        $this->assertSame('{{ page.title }}', $source->getCode());
        $this->assertSame('{{ page.title }}', $source->getName());
    }

    public function testGetSourceContextThrowsForLayoutTemplate(): void
    {
        $loader = new TwigStringLoader();

        $this->expectException(LoaderError::class);
        $loader->getSourceContext('layout.twig');
    }

    public function testGetSourceContextThrowsForHtmlFile(): void
    {
        $loader = new TwigStringLoader();

        $this->expectException(LoaderError::class);
        $loader->getSourceContext('layout.html');
    }

    public function testIsLayoutTemplate(): void
    {
        $loader = new TwigStringLoader();

        $this->assertTrue($loader->isLayoutTemplate('layout.twig'));
        $this->assertTrue($loader->isLayoutTemplate('layout.html'));
        $this->assertTrue($loader->isLayoutTemplate('@site/layout.twig'));
        $this->assertFalse($loader->isLayoutTemplate('{{ page.title }}'));
        $this->assertFalse($loader->isLayoutTemplate('plain text'));
        $this->assertFalse($loader->isLayoutTemplate('layout.md'));
        $this->assertFalse($loader->isLayoutTemplate(''));
    }

    public function testExists(): void
    {
        $loader = new TwigStringLoader();

        $this->assertTrue($loader->exists('{{ page.title }}'));
        $this->assertFalse($loader->exists('layout.twig'));
    }

    public function testGetCacheKeyIsIndependentOfTheTemplateName(): void
    {
        $loader = new TwigStringLoader();

        $this->assertSame(md5('a'), $loader->getCacheKey('a'));
        $this->assertSame(md5('b'), $loader->getCacheKey('b'));
        $this->assertNotSame($loader->getCacheKey('a'), $loader->getCacheKey('b'));
    }

    public function testIsFresh(): void
    {
        $loader = new TwigStringLoader();

        $this->assertTrue($loader->isFresh('any', time()));
    }
}
