<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins;

use herbie\Application;
use herbie\ApplicationPaths;

final class MarkdownSysPluginTest extends \Codeception\Test\Unit
{
    protected Application $app;

    protected function initApplication(string $appPath, string $sitePath): Application
    {
        $paths = new ApplicationPaths(
            $appPath,
            $sitePath,
            dirname(__DIR__, 4) . '/vendor',
            dirname(__DIR__, 2) . '/_data/web'
        );
        $app = new Application($paths);
        $app->getPluginManager()->init();
        $app->getTwigRenderer()->init();
        return $app;
    }

    protected function _setUp(): void
    {
        $this->app = $this->initApplication(
            dirname(__DIR__, 3),
            dirname(__DIR__) . '/Fixtures/site'
        );
    }

    public function testMarkdownFilter(): void
    {
        $this->assertSame(
            '<h1>This is markdown</h1>',
            $this->app->getTwigRenderer()->renderString('{{ "# This is markdown"|markdown }}')
        );
    }

    public function testMarkdownFilterWithDisabledFilter(): void
    {
        $app = $this->initApplication(
            dirname(__DIR__, 3),
            dirname(__DIR__) . '/Fixtures/markdown'
        );
        if ($app->getConfig()->getAsBool('components.twigRenderer.debug') === true) {
            $this->expectException(\Error::class);
        } else {
            $this->expectException(\Twig\Error\SyntaxError::class);
        }
        $app->getTwigRenderer()->renderString('{{ "# This is markdown"|markdown }}');
    }

    public function testMarkdownFunction(): void
    {
        $this->assertSame(
            '<h1>This is markdown</h1>',
            $this->app->getTwigRenderer()->renderString('{{ markdown("# This is markdown") }}')
        );
    }

    public function testMarkdownFunctionWithDisabledFunction(): void
    {
        $app = $this->initApplication(
            dirname(__DIR__, 3),
            dirname(__DIR__) . '/Fixtures/markdown'
        );
        $isDebug = $app->getConfig()->getAsBool('components.twigRenderer.debug');
        if ($isDebug === true) {
            $this->expectException(\Error::class);
        } else {
            $this->expectException(\Twig\Error\SyntaxError::class);
        }
        $app->getTwigRenderer()->renderString('{{ markdown("# This is markdown") }}');
    }
}
