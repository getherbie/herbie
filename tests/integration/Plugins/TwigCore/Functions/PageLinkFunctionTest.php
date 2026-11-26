<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class PageLinkFunctionTest extends Unit
{
    protected UnitTester $tester;

    // @deprecated Since 3.3.0, remove in 4.0 together with link_page().
    public function testPowerOn(): void
    {
        $this->assertEquals(
            '<span class="link link--page"><a href="/route" class="link__label">label</a></span>',
            $this->twig()->renderString('{{ link_page("route", "label") }}'),
        );
    }

    public function testPageLink(): void
    {
        $this->assertSame(
            '<span class="link link--page"><a href="/route" class="link__label">label</a></span>',
            $this->twig()->renderString('{{ page_link("route", "label") }}'),
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
