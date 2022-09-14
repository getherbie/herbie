<?php

declare(strict_types=1);

namespace Tests\Unit\Twig\Tests;

use herbie\Application;
use herbie\SystemException;
use herbie\TwigRenderer;
use Twig\Error\LoaderError;

final class WritableTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;

    protected function _setUp(): void
    {
        try {
            $app = new Application(dirname(__DIR__, 2) . '/Fixtures/site', dirname(__DIR__, 4) . '/vendor');
            $this->twigRenderer = $app->getContainer()->get(TwigRenderer::class);
            $this->twigRenderer->init();
        } catch (LoaderError|SystemException $e) {
        }
    }

    public function testWritableWithoutCondition(): void
    {
        $twig = <<<TWIG
            {{- writable -}}
        TWIG;
        $this->assertSame('', $this->twigRenderer->renderString($twig));
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
