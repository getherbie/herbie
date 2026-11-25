<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class DownloadLinkFunctionTest extends Unit
{
    protected UnitTester $tester;

    public function testPowerOn(): void
    {
        $this->assertEquals(
            '<span class="link link--media"><a href="/media/dummy.pdf?download" alt="" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ link_download("dummy.pdf") }}'),
        );
    }

    public function testMediaPrefixIsOptional(): void
    {
        $this->assertEquals(
            '<span class="link link--media"><a href="/media/dummy.pdf?download" alt="" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ link_download("/media/dummy.pdf") }}'),
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
