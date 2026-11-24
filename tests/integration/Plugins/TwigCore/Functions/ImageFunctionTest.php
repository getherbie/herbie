<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class ImageFunctionTest extends Unit
{
    protected UnitTester $tester;

    public function testMediaPathIsServedByHerbie(): void
    {
        $this->assertEquals(
            '<img src="/media/dummy-200x100.gif" alt="">',
            $this->twig()->renderString('{{ image("/media/dummy-200x100.gif") }}'),
        );
    }

    public function testMediaPathWithoutLeadingSlashIsAccepted(): void
    {
        $this->assertEquals(
            '<img src="/media/dummy-200x100.gif" alt="">',
            $this->twig()->renderString('{{ image("media/dummy-200x100.gif") }}'),
        );
    }

    public function testOtherPathsAreRelativeToTheWebRoot(): void
    {
        $this->assertEquals(
            '<img src="/assets/logo.png" alt="">',
            $this->twig()->renderString('{{ image("assets/logo.png") }}'),
        );
        $this->assertEquals(
            '<img src="/logo.png" alt="">',
            $this->twig()->renderString('{{ image("/logo.png") }}'),
        );
    }

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site',
        );
    }
}
