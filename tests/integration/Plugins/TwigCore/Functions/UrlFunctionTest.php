<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class UrlFunctionTest extends Unit
{
    protected TwigRenderer $twigRenderer;
    protected UnitTester $tester;

    // @deprecated Since 3.3.0, remove in 4.0 together with url_rel().
    public function testUrlWithoutRoute(): void
    {
        $twig = '{{ url_rel() }}';
        $this->assertSame('/', $this->twig()->renderString($twig));
    }

    public function testPageUrlWithoutRoute(): void
    {
        $this->assertSame('/', $this->twig()->renderString('{{ page_url() }}'));
    }

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site',
        );
    }

    // @deprecated Since 3.3.0, remove in 4.0 together with url_rel().
    public function testUrlWithValidRoutes(): void
    {
        $this->assertSame('/one', $this->twig()->renderString('{{ url_rel("one") }}'));
        $this->assertSame('/one/two', $this->twig()->renderString('{{ url_rel("one/two") }}'));
        $this->assertSame('/one/two/three', $this->twig()->renderString('{{ url_rel("one/two/three") }}'));
    }

    public function testPageUrlWithValidRoutes(): void
    {
        $this->assertSame('/one', $this->twig()->renderString('{{ page_url("one") }}'));
        $this->assertSame('/one/two', $this->twig()->renderString('{{ page_url("one/two") }}'));
        $this->assertSame('/one/two/three', $this->twig()->renderString('{{ page_url("one/two/three") }}'));
    }
}
