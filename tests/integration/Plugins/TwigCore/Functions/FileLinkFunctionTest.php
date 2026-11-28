<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class FileLinkFunctionTest extends Unit
{
    protected UnitTester $tester;

    // @deprecated Since 3.3.0, remove in 4.0 together with link_file().
    public function testPowerOn(): void
    {
        $this->assertEquals(
            '<span class="link link--file"><a href="/dummy.pdf" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ link_file("dummy.pdf") }}'),
        );
    }

    // @deprecated Since 3.3.0, remove in 4.0 together with link_file().
    public function testMediaPathIsServedByHerbie(): void
    {
        $this->assertEquals(
            '<span class="link link--file"><a href="/media/dummy.pdf" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ link_file("/media/dummy.pdf") }}'),
        );
    }

    // @deprecated Since 3.3.0, remove in 4.0 together with link_download().
    public function testLinkDownloadPowerOn(): void
    {
        $this->assertEquals(
            '<span class="link link--media"><a href="/media/dummy.pdf?download" alt="" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ link_download("dummy.pdf") }}'),
        );
    }

    // @deprecated Since 3.3.0, remove in 4.0 together with link_download().
    public function testLinkDownloadMediaPrefixIsOptional(): void
    {
        $this->assertEquals(
            '<span class="link link--media"><a href="/media/dummy.pdf?download" alt="" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ link_download("/media/dummy.pdf") }}'),
        );
    }

    public function testFileLink(): void
    {
        $this->assertSame(
            '<span class="link link--file"><a href="/dummy.pdf" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ file_link("dummy.pdf") }}'),
        );
    }

    public function testFileLinkMediaPathIsServedByHerbie(): void
    {
        $this->assertSame(
            '<span class="link link--file"><a href="/media/dummy.pdf" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ file_link("/media/dummy.pdf") }}'),
        );
    }

    public function testFileLinkDownloadAddsTheDownloadParameter(): void
    {
        $this->assertSame(
            '<span class="link link--file"><a href="/media/dummy.pdf?download" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ file_link("/media/dummy.pdf", download: true) }}'),
        );
    }

    public function testFileLinkDownloadIgnoresWebRootPaths(): void
    {
        $this->assertSame(
            '<span class="link link--file"><a href="/dummy.pdf" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ file_link("dummy.pdf", download: true) }}'),
        );
    }

    public function testFileLinkAbsoluteIncludesSchemeAndHost(): void
    {
        $this->assertSame(
            '<span class="link link--file"><a href="http:/media/dummy.pdf" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ file_link("/media/dummy.pdf", absolute: true) }}'),
        );
    }

    public function testFileLinkCombinesAbsoluteAndDownload(): void
    {
        $this->assertSame(
            '<span class="link link--file"><a href="http:/media/dummy.pdf?download" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ file_link("/media/dummy.pdf", download: true, absolute: true) }}'),
        );
    }

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site',
        );
    }

    // TODO write more tests
}
