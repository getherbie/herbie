<?php

declare(strict_types=1);

namespace Tests\Unit\Twig\Functions;

use ArgumentCountError;
use herbie\Application;
use herbie\TwigRenderer;

final class UrlFunctionTest extends \Codeception\Test\Unit
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
        $twig = '{{ url() }}';
        $this->assertSame('/', $this->twigRenderer->renderString($twig));
    }

    public function testUrlWithValidRoutes(): void
    {
        $this->assertSame('/one', $this->twigRenderer->renderString('{{ url("one") }}'));
        $this->assertSame('/one/two', $this->twigRenderer->renderString('{{ url("one/two") }}'));
        $this->assertSame('/one/two/three', $this->twigRenderer->renderString('{{ url("one/two/three") }}'));
    }
}
