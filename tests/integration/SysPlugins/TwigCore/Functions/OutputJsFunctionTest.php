<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class OutputJsFunctionTest extends Unit
{
    protected UnitTester $tester;

    public function testPowerOn(): void
    {
        $twig = '{{ js_add("@site/assets/script.js") }}'
            . '{{ js_out() }}';
        $this->assertEquals(
            '<script src="/assets/assets/script.js"></script>',
            $this->twig()->renderString($twig)
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
