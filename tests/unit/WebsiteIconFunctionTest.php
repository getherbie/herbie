<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Config;
use Twig\TwigFunction;

final class WebsiteIconFunctionTest extends Unit
{
    public function testIconIsLoadedFromTheActiveTheme(): void
    {
        $this->assertStringContainsString('data-theme="blog.rot"', $this->renderIcon('blog.rot', 'herbie'));
    }

    public function testIconFallsBackToDefaultTheme(): void
    {
        $this->assertStringContainsString('data-theme="default"', $this->renderIcon('', 'herbie'));
    }

    private function renderIcon(string $theme, string $name): string
    {
        $sitePath = dirname(__DIR__) . '/integration/Fixtures/theme-site';
        $config = new Config([
            'theme' => $theme,
            'paths' => [
                'themes' => $sitePath . '/themes',
            ],
        ]);

        // LocalExtensionsPlugin includes site functions in method scope, so
        // $this->config is available to the included file.
        $loader = new class ($config) {
            public function __construct(public Config $config) {}

            public function load(): TwigFunction
            {
                return include dirname(__DIR__, 2) . '/website/site/extend/twig_functions/icon.php';
            }
        };

        return (string)($loader->load()->getCallable())($name);
    }
}
