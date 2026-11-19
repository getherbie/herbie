<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class OutputCssFunctionTest extends Unit
{
    protected UnitTester $tester;

    public function testPowerOn(): void
    {
        $twig = '{{ css_add("@site/assets/styles.css") }}'
            . '{{ css_out() }}';
        $this->assertEquals(
            '<link href="/assets/assets/styles.css" type="text/css" rel="stylesheet">',
            $this->twig()->renderString($twig),
        );
    }

    public function testThemeAssetIsResolvedFromTheActiveTheme(): void
    {
        $app = $this->tester->initApplication(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/theme-site',
        );

        $twig = '{{ css_add("@theme/css/site.css") }}'
            . '{{ css_out() }}';
        $this->assertEquals(
            '<link href="/assets/theme/css/site.css" type="text/css" rel="stylesheet">',
            $app->getTwigRenderer()->renderString($twig),
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
