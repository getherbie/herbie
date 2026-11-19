<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;

final class WebsiteThemeStylesTest extends Unit
{
    private const STYLES = __DIR__ . '/../../website/site/themes/default/css/styles.css';

    public function testColorsAreOnlyDefinedAsTokens(): void
    {
        $css = $this->styles();

        // Remove custom property declarations; any color left over is hardcoded.
        preg_match_all('/^\s*--[a-z0-9-]+\s*:[^;]+;/mi', $css, $declarations);
        $rules = str_replace($declarations[0], '', $css);

        preg_match_all('/#[0-9a-f]{3,8}\b|rgba?\(|hsla?\(/i', $rules, $colors);

        $this->assertSame([], $colors[0], 'Colors must be defined as CSS custom properties.');
    }

    public function testEveryUsedTokenIsDefinedOrHasAFallback(): void
    {
        $css = $this->styles();

        preg_match_all('/^\s*(--[a-z0-9-]+)\s*:/mi', $css, $definitions);
        $defined = array_flip($definitions[1]);

        preg_match_all('/var\(\s*(--[a-z0-9-]+)\s*([,)])/', $css, $references, PREG_SET_ORDER);
        $missing = [];
        foreach ($references as $reference) {
            if ($reference[2] === ',' || isset($defined[$reference[1]])) {
                continue;
            }
            $missing[] = $reference[1];
        }

        $this->assertSame([], array_unique($missing));
    }

    private function styles(): string
    {
        return (string)file_get_contents(self::STYLES);
    }
}
