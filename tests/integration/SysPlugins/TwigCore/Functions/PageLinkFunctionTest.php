<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class PageLinkFunctionTest extends Unit
{
    protected UnitTester $tester;

    public function testPowerOn(): void
    {
        $this->assertEquals(
            '<span class="link link--page"><a href="/route" class="link__label">label</a></span>',
            $this->twig()->renderString('{{ link_page("route", "label") }}')
        );
    }

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site'
        );
    }

    // TODO write more tests
}
