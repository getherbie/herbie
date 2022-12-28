<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class CssClassesFunctionTest extends Unit
{
    protected UnitTester $tester;

    public function testCssClassesFunction(): void
    {
        $expected = 'page-error theme-default layout-default language-de';
        $actual = $this->twig()->renderString('{{ css_classes() }}');
        $this->assertEquals($expected, $actual);
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
