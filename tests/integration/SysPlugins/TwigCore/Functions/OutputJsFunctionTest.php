<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use herbie\TwigRenderer;

final class OutputJsFunctionTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 3) . '/Fixtures/site',
            dirname(__DIR__, 5) . '/vendor'
        );
    }

    public function testPowerOn(): void
    {
        $twig = '{{ add_js("@site/assets/script.js") }}'
            . '{{ output_js() }}';
        $this->assertEquals(
            '<script src="/assets/assets/script.js"></script>',
            $this->twig()->renderString($twig)
        );
    }

    // TODO write more tests
}
