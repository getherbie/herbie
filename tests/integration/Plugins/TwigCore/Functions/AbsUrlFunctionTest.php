<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class AbsUrlFunctionTest extends Unit
{
    protected TwigRenderer $twigRenderer;
    protected UnitTester $tester;

    // @deprecated Since 3.3.0, remove in 4.0 together with url_abs().
    public function testUrlWithoutRoute(): void
    {
        $tests = [
            ['http:/', '{{ url_abs() }}'],
            ['http:/', '{{ url_abs("") }}'],
        ];
        foreach ($tests as $test) {
            $this->assertSame($test[0], $this->twig()->renderString($test[1]));
        }
    }

    public function testPageUrlAbsoluteWithoutRoute(): void
    {
        $this->assertSame('http:/', $this->twig()->renderString('{{ page_url(absolute: true) }}'));
        $this->assertSame('http:/', $this->twig()->renderString('{{ page_url("", absolute: true) }}'));
    }

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site',
        );
    }

    // @deprecated Since 3.3.0, remove in 4.0 together with url_abs().
    public function testUrlWithValidRoutes(): void
    {
        $tests = [
            ['http:/one', '{{ url_abs("one") }}'],
            ['http:/one/two', '{{ url_abs("one/two") }}'],
            ['http:/one/two/three', '{{ url_abs("one/two/three") }}'],
        ];
        foreach ($tests as $test) {
            $this->assertSame($test[0], $this->twig()->renderString($test[1]));
        }
    }

    public function testPageUrlAbsoluteWithValidRoutes(): void
    {
        $tests = [
            ['http:/one', '{{ page_url("one", absolute: true) }}'],
            ['http:/one/two', '{{ page_url("one/two", absolute: true) }}'],
            ['http:/one/two/three', '{{ page_url("one/two/three", absolute: true) }}'],
        ];
        foreach ($tests as $test) {
            $this->assertSame($test[0], $this->twig()->renderString($test[1]));
        }
    }
}
