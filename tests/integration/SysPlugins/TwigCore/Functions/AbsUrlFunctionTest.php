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
            ['http:/', '{{ urlAbs() }}'],
            ['http:/', '{{ urlAbs("") }}'],
        ];
        foreach ($tests as $test) {
            $this->assertSame($test[0], $this->twig()->renderString($test[1]));
        }
    }

    public function testUrlWithValidRoutes(): void
    {
        $tests = [
            ['http:/one', '{{ urlAbs("one") }}'],
            ['http:/one/two', '{{ urlAbs("one/two") }}'],
            ['http:/one/two/three', '{{ urlAbs("one/two/three") }}']
        ];
        foreach ($tests as $test) {
            $this->assertSame($test[0], $this->twig()->renderString($test[1]));
        }
    }
}
