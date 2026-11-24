<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class MediaFunctionsTest extends Unit
{
    protected UnitTester $tester;

    public function testFileUrlReturnsTheMediaUrl(): void
    {
        $this->assertSame('/media/dummy.pdf', $this->twig()->renderString('{{ file_url("/media/dummy.pdf") }}'));
        $this->assertSame('/media/dummy.pdf', $this->twig()->renderString('{{ file_url("media/dummy.pdf") }}'));
    }

    public function testFileUrlReturnsAWebRootUrlForOtherPaths(): void
    {
        $this->assertSame('/logo.png', $this->twig()->renderString('{{ file_url("/logo.png") }}'));
        $this->assertSame('/logo.png', $this->twig()->renderString('{{ file_url("logo.png") }}'));
    }

    public function testFileUrlAddsTheDownloadParameterForMediaFiles(): void
    {
        $this->assertSame(
            '/media/dummy.pdf?download',
            $this->twig()->renderString('{{ file_url("/media/dummy.pdf", {download: true}) }}'),
        );
    }

    public function testFileUrlIgnoresTheDownloadOptionForWebRootFiles(): void
    {
        $this->assertSame(
            '/logo.png',
            $this->twig()->renderString('{{ file_url("/logo.png", {download: true}) }}'),
        );
    }

    public function testFileUrlEncodesPathSegments(): void
    {
        $this->assertSame(
            '/media/sub%20dir/%C3%BCber%20uns.pdf',
            $this->twig()->renderString('{{ file_url("/media/sub dir/über uns.pdf") }}'),
        );
    }

    public function testFileUrlKeepsExternalUrlsAndAnchors(): void
    {
        $this->assertSame(
            'https://example.com/x.png',
            $this->twig()->renderString('{{ file_url("https://example.com/x.png") }}'),
        );
        $this->assertSame('#top', $this->twig()->renderString('{{ file_url("#top") }}'));
    }

    public function testFileUrlKeepsAQueryString(): void
    {
        $this->assertSame('/logo.png?v=2', $this->twig()->renderString('{{ file_url("/logo.png?v=2") }}'));
        // Twig escapes the ampersand, which is correct inside HTML attributes
        $this->assertSame(
            '/media/dummy.pdf?download&amp;v=2',
            $this->twig()->renderString('{{ file_url("/media/dummy.pdf?v=2", {download: true}) }}'),
        );
    }

    public function testEmbedReturnsAnIframe(): void
    {
        $this->assertSame(
            '<iframe src="/media/dummy.pdf" title="dummy.pdf"></iframe>',
            $this->twig()->renderString('{{ embed("/media/dummy.pdf") }}'),
        );
    }

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site',
        );
    }
}
