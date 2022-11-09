<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use herbie\TwigRenderer;
use UnitTester;

final class UrlFunctionTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;
    protected UnitTester $tester;

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site'
        );
    }

    public function testUrlWithoutRoute(): void
    {
        $twig = '{{ url() }}';
        $this->assertSame('/', $this->twig()->renderString($twig));
    }

    public function testUrlWithValidRoutes(): void
    {
        $this->assertSame('/one', $this->twig()->renderString('{{ url("one") }}'));
        $this->assertSame('/one/two', $this->twig()->renderString('{{ url("one/two") }}'));
        $this->assertSame('/one/two/three', $this->twig()->renderString('{{ url("one/two/three") }}'));
    }
}
