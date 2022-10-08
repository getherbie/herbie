<?php

declare(strict_types=1);

namespace tests\integration\SysPlugin;

use herbie\Application;
use herbie\ApplicationPaths;
use herbie\TwigRenderer;

final class TextileSysPluginTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;

    protected function initApplication(string $appPath, string $sitePath): TwigRenderer
    {
        $app = new Application(new ApplicationPaths($appPath, $sitePath));
        $app->getPluginManager()->init();
        ($twigRenderer = $app->getTwigRenderer())->init();
        return $twigRenderer;
    }

    protected function _setUp(): void
    {
        $this->twigRenderer = $this->initApplication(
            dirname(__DIR__, 3),
            dirname(__DIR__) . '/Fixtures/site'
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
            dirname(__DIR__, 3),
            dirname(__DIR__) . '/Fixtures/textile'
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
            dirname(__DIR__, 3),
            dirname(__DIR__) . '/Fixtures/textile'
        );
        $this->expectException(\Twig\Error\SyntaxError::class);
        $twigRenderer->renderString('{{ textile("# This is textile") }}');
    }
}
