<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class LinkFileFunctionTest extends Unit
{
    protected UnitTester $tester;

    public function testPowerOn(): void
    {
        $this->assertEquals(
            '<span class="link link--file"><a href="/dummy.pdf" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ link_file("dummy.pdf") }}'),
        );
    }

    public function testMediaPathIsServedByHerbie(): void
    {
        $this->assertEquals(
            '<span class="link link--file"><a href="/media/dummy.pdf" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ link_file("/media/dummy.pdf") }}'),
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
