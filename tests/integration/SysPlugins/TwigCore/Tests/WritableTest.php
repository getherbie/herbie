<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Tests;

use herbie\Application;
use herbie\TwigRenderer;

final class WritableTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;

    protected function _setUp(): void
    {
        $app = new Application(dirname(__DIR__, 3) . '/Fixtures/site', dirname(__DIR__, 5) . '/vendor');
        $app->getPluginManager()->init();
        $app->getTwigRenderer()->init();
        $this->twigRenderer = $app->getTwigRenderer();
    }

    public function testWritableWithoutCondition(): void
    {
        $twig = '{{- writable -}}';

        // disabled strict variables
        $this->twigRenderer->getTwigEnvironment()->disableStrictVariables();
        $this->assertSame('', $this->twigRenderer->renderString($twig));

        // enabled strict variables
        $this->expectException(\Twig\Error\RuntimeError::class);
        $this->twigRenderer->getTwigEnvironment()->enableStrictVariables();
        $this->twigRenderer->renderString($twig);
    }

    public function testWritableWithEmptyParam(): void
    {
        $twig = <<<TWIG
            {%- if '' is writable -%}yes{% else %}no{%- endif -%}
        TWIG;
        $this->assertSame('no', $this->twigRenderer->renderString($twig));
    }

    public function testWritableWithExistingAlias(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy.pdf' is writable -%}yes{% else %}no{%- endif -%}
        TWIG;
        $this->assertSame('yes', $this->twigRenderer->renderString($twig));
    }
    
    public function testWritableWithExistingAliasWithoutPermissions(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy-no-rights.pdf' is writable -%}yes{% else %}no{%- endif -%}
        TWIG;
        $this->assertSame('no', $this->twigRenderer->renderString($twig));
    }
    
    public function testReadableWithNotExistingAlias(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy-xyz.pdf' is writable -%}yes{% else %}no{%- endif -%}
        TWIG;
        $this->assertSame('no', $this->twigRenderer->renderString($twig));
    }
}
