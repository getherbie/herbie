<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class PageUrlFunctionTest extends Unit
{
    protected TwigRenderer $twigRenderer;
    protected UnitTester $tester;

    // @deprecated Since 3.3.0, remove in 4.0 together with url_rel().
    public function testUrlRelWithoutRoute(): void
    {
        $twig = '{{ url_rel() }}';
        $this->assertSame('/', $this->twig()->renderString($twig));
    }

    // @deprecated Since 3.3.0, remove in 4.0 together with url_abs().
    public function testUrlAbsWithoutRoute(): void
    {
        $tests = [
            ['http:/', '{{ url_abs() }}'],
            ['http:/', '{{ url_abs("") }}'],
        ];
        foreach ($tests as $test) {
            $this->assertSame($test[0], $this->twig()->renderString($test[1]));
        }
    }

    public function testPageUrlWithoutRoute(): void
    {
        $this->assertSame('/', $this->twig()->renderString('{{ page_url() }}'));
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

    // @deprecated Since 3.3.0, remove in 4.0 together with url_rel().
    public function testUrlRelWithValidRoutes(): void
    {
        $this->assertSame('/one', $this->twig()->renderString('{{ url_rel("one") }}'));
        $this->assertSame('/one/two', $this->twig()->renderString('{{ url_rel("one/two") }}'));
        $this->assertSame('/one/two/three', $this->twig()->renderString('{{ url_rel("one/two/three") }}'));
    }

    // @deprecated Since 3.3.0, remove in 4.0 together with url_abs().
    public function testUrlAbsWithValidRoutes(): void
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

    public function testPageUrlWithValidRoutes(): void
    {
        $this->assertSame('/one', $this->twig()->renderString('{{ page_url("one") }}'));
        $this->assertSame('/one/two', $this->twig()->renderString('{{ page_url("one/two") }}'));
        $this->assertSame('/one/two/three', $this->twig()->renderString('{{ page_url("one/two/three") }}'));
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
