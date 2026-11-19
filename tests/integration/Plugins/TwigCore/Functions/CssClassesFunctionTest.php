<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

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

    public function testCssClassesFunctionUsesTheConfiguredTheme(): void
    {
        $app = $this->tester->initApplication(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/theme-site',
        );

        $expected = 'page-error theme-blog-rot layout-default language-de';
        $actual = $app->getTwigRenderer()->renderString('{{ css_classes() }}');
        $this->assertEquals($expected, $actual);
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
