<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;

final class ContactFormStyleTest extends Unit
{
    private const string FORM = __DIR__ . '/../../plugins/contact/templates/form.twig';

    public function testAltchaColorsDoNotDependOnTheWebsitesDarkModeAttribute(): void
    {
        $this->assertStringNotContainsString('data-theme', $this->form());
    }

    public function testAltchaColorsFallBackToSystemColors(): void
    {
        preg_match('/altcha-widget\s*\{([^}]*)\}/', $this->form(), $matches);
        $block = $matches[1] ?? '';

        $this->assertStringContainsString('var(--surface, Canvas)', $block);
        $this->assertStringContainsString('var(--text, CanvasText)', $block);
        $this->assertSame(0, preg_match('/#[0-9a-f]{3,8}\b/i', $block));
        $this->assertStringNotContainsString('#a94f4f', $this->form());
    }

    private function form(): string
    {
        return (string)file_get_contents(self::FORM);
    }
}
