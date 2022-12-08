<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Tests;

use herbie\TwigRenderer;
use UnitTester;

final class WritableTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;

    protected UnitTester $tester;

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site'
        );
    }

    public function testWritableWithoutCondition(): void
    {
        $twig = '{{- writable -}}';

        // disabled strict variables
        $twigInstance = $this->twig();
        $twigInstance->getTwigEnvironment()->disableStrictVariables();
        $this->assertSame('', $twigInstance->renderString($twig));

        // enabled strict variables
        $this->expectException(\Twig\Error\RuntimeError::class);
        $twigInstance = $this->twig();
        $twigInstance->getTwigEnvironment()->enableStrictVariables();
        $twigInstance->renderString($twig);
    }

    public function testWritableWithEmptyParam(): void
    {
        $twig = <<<TWIG
            {%- if '' is fileWritable -%}yes{% else %}no{%- endif -%}
        TWIG;
        $this->assertSame('no', $this->twig()->renderString($twig));
    }

    public function testWritableWithExistingAlias(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy.pdf' is fileWritable -%}yes{% else %}no{%- endif -%}
        TWIG;
        $this->assertSame('yes', $this->twig()->renderString($twig));
    }

    public function testWritableWithExistingAliasWithoutPermissions(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy-no-rights.pdf' is fileWritable -%}yes{% else %}no{%- endif -%}
        TWIG;
        $this->assertSame('no', $this->twig()->renderString($twig));
    }

    public function testReadableWithNotExistingAlias(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy-xyz.pdf' is fileWritable -%}yes{% else %}no{%- endif -%}
        TWIG;
        $this->assertSame('no', $this->twig()->renderString($twig));
    }
}
