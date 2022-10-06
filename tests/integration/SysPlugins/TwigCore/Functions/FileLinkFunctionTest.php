<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use herbie\TwigRenderer;

final class FileLinkFunctionTest extends \Codeception\Test\Unit
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
            '<span class="link link--download"><a href="/download/dummy.pdf" alt="" class="link__label">dummy.pdf</a></span>',
            $this->twig()->renderString('{{ file_link("dummy.pdf") }}')
        );
    }

    // TODO write more tests
}
