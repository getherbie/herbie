<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use herbie\TwigRenderer;
use UnitTester;

final class FileLinkFunctionTest extends \Codeception\Test\Unit
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
            '<span class="link link--media"><a href="/download/dummy.pdf" alt="" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ link_media("dummy.pdf") }}')
        );
    }

    // TODO write more tests
}
