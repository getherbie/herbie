<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class UrlFunctionTest extends Unit
{
    protected TwigRenderer $twigRenderer;
    protected UnitTester $tester;

    public function testUrlWithoutRoute(): void
    {
        $twig = '{{ url_rel() }}';
        $this->assertSame('/', $this->twig()->renderString($twig));
    }

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site'
        );
    }

    public function testUrlWithValidRoutes(): void
    {
        $this->assertSame('/one', $this->twig()->renderString('{{ url_rel("one") }}'));
        $this->assertSame('/one/two', $this->twig()->renderString('{{ url_rel("one/two") }}'));
        $this->assertSame('/one/two/three', $this->twig()->renderString('{{ url_rel("one/two/three") }}'));
    }
}
