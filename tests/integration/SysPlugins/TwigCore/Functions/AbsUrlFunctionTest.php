<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use herbie\Application;
use herbie\ApplicationPaths;
use herbie\TwigRenderer;

final class AbsUrlFunctionTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;

    protected function _setUp(): void
    {
        $_SERVER['SCRIPT_NAME'] = '';
        $app = new Application(new ApplicationPaths(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site',
            dirname(__DIR__, 5) . '/vendor',
            dirname(__DIR__, 4) . '/_data/web'
        ));
        $app->getPluginManager()->init();
        $app->getTwigRenderer()->init();
        $this->twigRenderer = $app->getTwigRenderer();
    }

    public function testUrlWithoutRoute(): void
    {
        $tests = [
            ['http:/', '{{ abs_url() }}'],
            ['http:/', '{{ abs_url("") }}'],
        ];
        foreach ($tests as $test) {
            $this->assertSame($test[0], $this->twigRenderer->renderString($test[1]));
        }
    }

    public function testUrlWithValidRoutes(): void
    {
        $tests = [
            ['http:/one', '{{ abs_url("one") }}'],
            ['http:/one/two', '{{ abs_url("one/two") }}'],
            ['http:/one/two/three', '{{ abs_url("one/two/three") }}']
        ];
        foreach ($tests as $test) {
            $this->assertSame($test[0], $this->twigRenderer->renderString($test[1]));
        }
    }
}
