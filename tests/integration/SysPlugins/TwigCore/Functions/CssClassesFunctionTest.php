<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use herbie\TwigRenderer;
use UnitTester;

final class CssClassesFunctionTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site'
        );
    }

    public function testCssClassesFunction(): void
    {
        $expected = 'page-error theme-default layout-default language-de';
        $actual = $this->twig()->renderString('{{ css_classes() }}');
        $this->assertEquals($expected, $actual);
    }

    // TODO write more tests
}
