<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class AbsUrlFunctionTest extends Unit
{
    protected TwigRenderer $twigRenderer;
    protected UnitTester $tester;

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

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site'
        );
    }

    public function testUrlWithValidRoutes(): void
    {
        $tests = [
            ['http:/one', '{{ url_abs("one") }}'],
            ['http:/one/two', '{{ url_abs("one/two") }}'],
            ['http:/one/two/three', '{{ url_abs("one/two/three") }}']
        ];
        foreach ($tests as $test) {
            $this->assertSame($test[0], $this->twig()->renderString($test[1]));
        }
    }
}
