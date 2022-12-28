<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins;

use Codeception\Test\Unit;
use herbie\Application;
use herbie\ApplicationPaths;
use herbie\TwigRenderer;
use Twig\Error\SyntaxError;

final class TextileSysPluginTest extends Unit
{
    protected TwigRenderer $twigRenderer;

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
        $this->expectException(SyntaxError::class);
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
        $this->expectException(SyntaxError::class);
        $twigRenderer->renderString('{{ textile("# This is textile") }}');
    }

    protected function _setUp(): void
    {
        $this->twigRenderer = $this->initApplication(
            dirname(__DIR__, 3),
            dirname(__DIR__) . '/Fixtures/site'
        );
    }

    protected function initApplication(string $appPath, string $sitePath): TwigRenderer
    {
        $app = new Application(
            new ApplicationPaths(
                $appPath,
                $sitePath,
                dirname(__DIR__, 5) . '/vendor',
                dirname(__DIR__, 2) . '/_data/web'
            )
        );
        $app->getPluginManager()->init();
        ($twigRenderer = $app->getTwigRenderer())->init();
        return $twigRenderer;
    }
}
