<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;

final class FrameworkCssPrefixTest extends Unit
{
    private const array TOKEN_PREFIXES = ['--herbie-', '--altcha-'];

    public function testStyleBlocksUsePrefixedClassSelectors(): void
    {
        $invalid = [];
        foreach ($this->styleBlocks() as $file => $css) {
            preg_match_all('/\.([a-z][a-z0-9-]*)/i', $css, $matches);
            foreach ($matches[1] as $class) {
                if (!str_starts_with($class, 'herbie-')) {
                    $invalid[] = basename($file) . ': .' . $class;
                }
            }
        }

        $this->assertSame([], $invalid, 'Framework CSS classes must start with herbie-.');
    }

    public function testStyleBlocksDefineOnlyPrefixedTokens(): void
    {
        $invalid = [];
        foreach ($this->styleBlocks() as $file => $css) {
            preg_match_all('/(--[a-z][a-z0-9-]*)\s*:/i', $css, $matches);
            foreach ($matches[1] as $token) {
                if (!$this->isAllowedToken($token)) {
                    $invalid[] = basename($file) . ': ' . $token;
                }
            }
        }

        $this->assertSame([], $invalid, 'Framework CSS tokens must start with --herbie- (or --altcha-).');
    }

    private function isAllowedToken(string $token): bool
    {
        foreach (self::TOKEN_PREFIXES as $prefix) {
            if (str_starts_with($token, $prefix)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<string, string>
     */
    private function styleBlocks(): array
    {
        $root = dirname(__DIR__, 2);
        $files = array_merge(
            glob($root . '/templates/snippets/*.twig') ?: [],
            glob($root . '/plugins/*/templates/*.twig') ?: [],
        );

        $blocks = [];
        foreach ($files as $file) {
            // Disabled debug blocks live inside Twig comments and are not shipped CSS.
            $content = preg_replace('/{#.*?#}/s', '', (string)file_get_contents($file)) ?? '';
            preg_match_all('/<style>(.*?)<\/style>/s', $content, $matches);
            foreach ($matches[1] as $css) {
                $blocks[$file] = ($blocks[$file] ?? '') . $css;
            }
        }
        return $blocks;
    }
}
