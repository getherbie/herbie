<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins\TwigCore\Tests;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use Twig\Error\RuntimeError;
use UnitTester;

final class WritableTest extends Unit
{
    protected TwigRenderer $twigRenderer;

    protected UnitTester $tester;

    public function testWritableWithoutCondition(): void
    {
        $twig = '{{- writable -}}';

        // disabled strict variables
        $twigInstance = $this->twig();
        $twigInstance->getTwigEnvironment()->disableStrictVariables();
        $this->assertSame('', $twigInstance->renderString($twig));

        // enabled strict variables
        $this->expectException(RuntimeError::class);
        $twigInstance = $this->twig();
        $twigInstance->getTwigEnvironment()->enableStrictVariables();
        $twigInstance->renderString($twig);
    }

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site'
        );
    }

    public function testWritableWithEmptyParam(): void
    {
        $twig = <<<TWIG
            {%- if '' is file_writable -%}yes{% else %}no{%- endif -%}
        TWIG;
        $this->assertSame('no', $this->twig()->renderString($twig));
    }

    public function testWritableWithExistingAlias(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy.pdf' is file_writable -%}yes{% else %}no{%- endif -%}
        TWIG;
        $this->assertSame('yes', $this->twig()->renderString($twig));
    }

    public function testWritableWithExistingAliasWithoutPermissions(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy-no-rights.pdf' is file_writable -%}yes{% else %}no{%- endif -%}
        TWIG;
        $this->assertSame('no', $this->twig()->renderString($twig));
    }

    public function testReadableWithNotExistingAlias(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy-xyz.pdf' is file_writable -%}yes{% else %}no{%- endif -%}
        TWIG;
        $this->assertSame('no', $this->twig()->renderString($twig));
    }
}
