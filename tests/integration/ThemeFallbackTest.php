<?php

declare(strict_types=1);

namespace herbie\tests\integration;

use Codeception\Test\Unit;
use UnexpectedValueException;
use UnitTester;

final class ThemeFallbackTest extends Unit
{
    protected UnitTester $tester;

    public function testTemplateFromParentThemeIsUsedWhenChildHasNoTemplate(): void
    {
        $this->assertSame("from default\n", $this->renderTemplate('fallback.twig'));
    }

    public function testChildThemeTemplateWinsOverParent(): void
    {
        $this->assertSame("from blog.rot\n", $this->renderTemplate('override.twig'));
    }

    public function testEmptyThemeIsRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Config "theme" must not be empty');

        $this->tester->initApplication(
            dirname(__DIR__, 2),
            __DIR__ . '/Fixtures/theme-empty',
        );
    }

    private function renderTemplate(string $name): string
    {
        $app = $this->tester->initApplication(
            dirname(__DIR__, 2),
            __DIR__ . '/Fixtures/theme-site',
        );
        return $app->getTwigRenderer()->renderTemplate($name);
    }
}
