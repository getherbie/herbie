<?php

declare(strict_types=1);

namespace integration\SysPlugin;

use herbie\Application;
use herbie\Config;
use herbie\TwigRenderer;

final class MarkdownTest extends \Codeception\Test\Unit
{
    protected Config $config;
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
        $this->twigRenderer = $this->initApplication(dirname(__DIR__) . '/Fixtures/site', dirname(__DIR__, 3) . '/vendor');
    }

    public function testMarkdownFilter(): void
    {
        $this->assertSame(
            '<h1>This is markdown</h1>',
            $this->twigRenderer->renderString('{{ "# This is markdown"|markdown }}')
        );
    }

    public function testMarkdownFilterWithDisabledFilter(): void
    {
        $twigRenderer = $this->initApplication(
            dirname(__DIR__) . '/Fixtures/markdown',
            dirname(__DIR__, 3) . '/vendor'
        );
        $this->expectException(\Twig\Error\SyntaxError::class);
        $twigRenderer->renderString('{{ "# This is markdown"|markdown }}');
    }
    
    public function testMarkdownFunction(): void
    {
        $this->assertSame(
            '<h1>This is markdown</h1>',
            $this->twigRenderer->renderString('{{ markdown("# This is markdown") }}')
        );
    }

    public function testMarkdownFunctionWithDisabledFunction(): void
    {
        $twigRenderer = $this->initApplication(
            dirname(__DIR__) . '/Fixtures/markdown',
            dirname(__DIR__, 3) . '/vendor'
        );
        $this->expectException(\Twig\Error\SyntaxError::class);
        $twigRenderer->renderString('{{ markdown("# This is markdown") }}');
    }
}
