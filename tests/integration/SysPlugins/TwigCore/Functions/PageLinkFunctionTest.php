<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use herbie\TwigRenderer;
use UnitTester;

final class PageLinkFunctionTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site'
        );
    }

    public function testPowerOn(): void
    {
        $this->assertEquals(
            '<span class="link link--internal"><a href="vendor/bin/codecept/route" class="link__label">label</a></span>',
            $this->twig()->renderString('{{ page_link("route", "label") }}')
        );
    }

    // TODO write more tests
}
