<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class MediaFunctionsTest extends Unit
{
    protected UnitTester $tester;

    public function testMediaUrlReturnsThePublicMediaUrl(): void
    {
        $this->assertSame('/media/dummy.pdf', $this->twig()->renderString('{{ media_url("dummy.pdf") }}'));
        $this->assertSame('/media/dummy.pdf', $this->twig()->renderString('{{ media_url("@media/dummy.pdf") }}'));
    }

    public function testMediaUrlReturnsThePublicDownloadUrl(): void
    {
        $this->assertSame('/download/dummy.pdf', $this->twig()->renderString('{{ media_url("dummy.pdf", "urls.download") }}'));
    }

    public function testEmbedReturnsAnIframe(): void
    {
        $this->assertSame(
            '<iframe src="/media/dummy.pdf" title="dummy.pdf"></iframe>',
            $this->twig()->renderString('{{ embed("dummy.pdf") }}'),
        );
    }

    public function testDownloadAliasCreatesAnAttachmentLink(): void
    {
        $this->assertSame(
            '<span class="link link--media"><a href="/download/dummy.pdf" alt="" class="link__label">PDF</a></span>',
            $this->twig()->renderString('{{ download("dummy.pdf", "PDF") }}'),
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
