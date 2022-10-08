<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use herbie\TwigRenderer;
use UnitTester;

final class OutputCssFunctionTest extends \Codeception\Test\Unit
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
        $twig = '{{ add_css("@site/assets/styles.css") }}'
            . '{{ output_css() }}';
        $this->assertEquals(
            '<link href="/assets/assets/styles.css" type="text/css" rel="stylesheet">',
            $this->twig()->renderString($twig)
        );
    }

    // TODO write more tests
}
