<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins;

use Codeception\Test\Unit;
use Error;
use herbie\Application;
use herbie\ApplicationPaths;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class MarkdownPluginTest extends Unit
{
    protected Application $app;

    public function testMarkdownFilter(): void
    {
        $this->assertSame(
            '<h1>This is markdown</h1>',
            $this->app->getTwigRenderer()->renderString('{{ "# This is markdown"|markdown }}'),
        );
    }

    public function testMarkdownFilterWithDisabledFilter(): void
    {
        $app = $this->initApplication(
            dirname(__DIR__, 3),
            dirname(__DIR__) . '/Fixtures/markdown',
        );
        $this->expectException(RuntimeError::class);
        $app->getTwigRenderer()->renderString('{{ "# This is markdown"|markdown }}');
    }

    public function testMarkdownFunction(): void
    {
        $this->assertSame(
            '<h1>This is markdown</h1>',
            $this->app->getTwigRenderer()->renderString('{{ markdown("# This is markdown") }}'),
        );
    }

    public function testMarkdownFunctionWithDisabledFunction(): void
    {
        $app = $this->initApplication(
            dirname(__DIR__, 3),
            dirname(__DIR__) . '/Fixtures/markdown',
        );
        $this->expectException(RuntimeError::class);
        $app->getTwigRenderer()->renderString('{{ markdown("# This is markdown") }}');
    }

    public function testConfiguredSmartPunctExtension(): void
    {
        $app = $this->initApplication(
            dirname(__DIR__, 3),
            dirname(__DIR__) . '/Fixtures/markdown-smartpunct',
        );

        $html = $app->getTwigRenderer()->renderString('{{ \'"Hi" -- ...\'|markdown }}');

        $this->assertStringContainsString('“Hi”', $html);
        $this->assertStringContainsString('–', $html);
        $this->assertStringContainsString('…', $html);
    }

    protected function _setUp(): void
    {
        $this->app = $this->initApplication(
            dirname(__DIR__, 3),
            dirname(__DIR__) . '/Fixtures/site',
        );
    }

    protected function initApplication(string $appPath, string $sitePath): Application
    {
        $paths = new ApplicationPaths(
            $appPath,
            $sitePath,
            dirname(__DIR__, 4) . '/vendor',
            dirname(__DIR__, 2) . '/_data/web',
        );
        $app = new Application($paths);
        $app->getPluginManager()->init();
        $app->getTwigRenderer()->init();
        return $app;
    }
}
