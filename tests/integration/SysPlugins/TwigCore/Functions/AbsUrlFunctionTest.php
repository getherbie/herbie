<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use herbie\Application;
use herbie\ApplicationPaths;
use herbie\TwigRenderer;
use UnitTester;

final class AbsUrlFunctionTest extends \Codeception\Test\Unit
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
        $tests = [
            ['http:/', '{{ h_url_abs() }}'],
            ['http:/', '{{ h_url_abs("") }}'],
        ];
        foreach ($tests as $test) {
            $this->assertSame($test[0], $this->twig()->renderString($test[1]));
        }
    }

    public function testUrlWithValidRoutes(): void
    {
        $tests = [
            ['http:/one', '{{ h_url_abs("one") }}'],
            ['http:/one/two', '{{ h_url_abs("one/two") }}'],
            ['http:/one/two/three', '{{ h_url_abs("one/two/three") }}']
        ];
        foreach ($tests as $test) {
            $this->assertSame($test[0], $this->twig()->renderString($test[1]));
        }
    }
}
