<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use herbie\TwigRenderer;
use UnitTester;

final class OutputJsFunctionTest extends \Codeception\Test\Unit
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
        $twig = '{{ h_js_add("@site/assets/script.js") }}'
            . '{{ h_js_out() }}';
        $this->assertEquals(
            '<script src="/assets/assets/script.js"></script>',
            $this->twig()->renderString($twig)
        );
    }

    // TODO write more tests
}
