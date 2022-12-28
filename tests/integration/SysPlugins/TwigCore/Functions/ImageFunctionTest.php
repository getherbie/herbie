<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins\TwigCore\Functions;

use herbie\TwigRenderer;
use UnitTester;

final class ImageFunctionTest extends \Codeception\Test\Unit
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
        $this->assertEquals(
            '<img src="/media/dummy-200x100.gif" alt="">',
            $this->twig()->renderString('{{ image("media/dummy-200x100.gif") }}')
        );
    }
}
