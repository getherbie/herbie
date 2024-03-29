<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins\TwigCore\Tests;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use Twig\Error\RuntimeError;
use UnitTester;

final class ReadableTest extends Unit
{
    protected UnitTester $tester;

    protected TwigRenderer $twigRenderer;

    public function testReadableWithoutCondition(): void
    {
        $twig = '{{- readable -}}';

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

    public function testReadableWithEmptyParam(): void
    {
        $twig = <<<TWIG
            {%- if '' is file_readable -%}readable{% else %}not readable{%- endif -%}
        TWIG;
        $this->assertSame('not readable', $this->twig()->renderString($twig));
    }

    public function testReadableWithExistingAlias(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy.pdf' is file_readable -%}is readable{% else %}not readable{%- endif -%}
        TWIG;
        $this->assertSame('is readable', $this->twig()->renderString($twig));
    }

    public function testReadableWithExistingAliasWithoutPermissions(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy-no-rights.pdf' is file_readable -%}is readable{% else %}not readable{%- endif -%}
        TWIG;
        $this->assertSame('not readable', $this->twig()->renderString($twig));
    }

    public function testReadableWithNotExistingAlias(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy-xyz.pdf' is file_readable -%}is readable{% else %}not readable{%- endif -%}
        TWIG;
        $this->assertSame('not readable', $this->twig()->renderString($twig));
    }
}
