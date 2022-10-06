<?php

declare(strict_types=1);

namespace tests\integration\SysPlugin;

use herbie\Application;
use herbie\TwigRenderer;

final class TextileSysPluginTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;

    protected function initApplication(string $sitePath, string $vendorPath): TwigRenderer
    {
        $app = new Application($sitePath, $vendorPath);
        $app->getPluginManager()->init();
        ($twigRenderer = $app->getTwigRenderer())->init();
        return $twigRenderer;
    }

    protected function _setUp(): void
    {
        $this->twigRenderer = $this->initApplication(
            dirname(__DIR__) . '/Fixtures/site',
            dirname(__DIR__, 3) . '/vendor'
        );
    }

    public function testTextileFilter(): void
    {
        $this->assertSame(
            '<h1>This is textile</h1>',
            $this->twigRenderer->renderString('{{ "h1. This is textile"|textile }}')
        );
    }

    public function testTextileFilterWithDisabledFilter(): void
    {
        $twigRenderer = $this->initApplication(
            dirname(__DIR__) . '/Fixtures/textile',
            dirname(__DIR__, 3) . '/vendor'
        );
        $this->expectException(\Twig\Error\SyntaxError::class);
        $twigRenderer->renderString('{{ "h2. This is textile"|textile }}');
    }

    public function testTextileFunction(): void
    {
        $this->assertSame(
            '<h1>This is textile</h1>',
            $this->twigRenderer->renderString('{{ textile("h1. This is textile") }}')
        );
    }

    public function testTextileFunctionWithDisabledFunction(): void
    {
        $twigRenderer = $this->initApplication(
            dirname(__DIR__) . '/Fixtures/textile',
            dirname(__DIR__, 3) . '/vendor'
        );
        $this->expectException(\Twig\Error\SyntaxError::class);
        $twigRenderer->renderString('{{ textile("# This is textile") }}');
    }
}
