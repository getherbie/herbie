<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use herbie\TwigRenderer;

final class ImageFunctionTest extends \Codeception\Test\Unit
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
        $this->assertEquals(
            '<img src="/media/dummy-200x100.gif" alt="">',
            $this->twig()->renderString('{{ image("media/dummy-200x100.gif") }}')
        );
    }
}
