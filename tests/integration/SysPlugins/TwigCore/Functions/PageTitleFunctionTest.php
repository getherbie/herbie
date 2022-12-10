<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use herbie\TwigRenderer;
use UnitTester;

final class PageTitleFunctionTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site'
        );
    }

    public function testPageTitleFunction(): void
    {
        $expected = 'Index';
        $actual = $this->twig()->renderString('{{ h_page_title() }}');
        $this->assertEquals($expected, $actual);
    }

    // TODO write more tests
}
