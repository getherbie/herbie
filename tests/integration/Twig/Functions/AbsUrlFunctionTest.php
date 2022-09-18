<?php

declare(strict_types=1);

namespace Tests\Unit\Twig\Functions;

use herbie\Application;
use herbie\TwigRenderer;

final class AbsUrlFunctionTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;
    
    protected function _setUp(): void
    {
        $_SERVER['SCRIPT_NAME'] = '';
        $app = new Application(dirname(__DIR__, 2) . '/Fixtures/site', dirname(__DIR__, 4) . '/vendor');
        ($this->twigRenderer = $app->getTwigRenderer())->init();
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
