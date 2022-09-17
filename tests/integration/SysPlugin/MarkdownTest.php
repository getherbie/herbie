<?php

declare(strict_types=1);

namespace integration\SysPlugin;

use herbie\Application;

final class MarkdownTest extends \Codeception\Test\Unit
{
    protected Application $app;

    protected function initApplication(string $sitePath, string $vendorPath): Application
    {
        $app = new Application($sitePath, $vendorPath);
        $app->getPluginManager()->init();
        $app->getTwigRenderer()->init();
        return $app;
    }
    
    protected function _setUp(): void
    {
        $this->app = $this->initApplication(
            dirname(__DIR__) . '/Fixtures/site',
            dirname(__DIR__, 3) . '/vendor'
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
            dirname(__DIR__) . '/Fixtures/markdown',
            dirname(__DIR__, 3) . '/vendor'
        );
        if ($app->getConfig()->getAsBool('twig.debug') === true) {
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
            dirname(__DIR__) . '/Fixtures/markdown',
            dirname(__DIR__, 3) . '/vendor'
        );
        $isDebug = $app->getConfig()->getAsBool('twig.debug');
        if ($isDebug === true) {
            $this->expectException(\Error::class);
        } else {
            $this->expectException(\Twig\Error\SyntaxError::class);
        }
        $app->getTwigRenderer()->renderString('{{ markdown("# This is markdown") }}');
    }
}
