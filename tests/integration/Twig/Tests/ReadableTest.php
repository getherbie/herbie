<?php

declare(strict_types=1);

namespace Tests\Unit\Twig\Tests;

use herbie\Application;
use herbie\TwigRenderer;

final class ReadableTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;

    protected function _setUp(): void
    {
        $app = new Application(dirname(__DIR__, 2) . '/Fixtures/site', dirname(__DIR__, 4) . '/vendor');
        ($this->twigRenderer = $app->getTwigRenderer())->init();
    }

    public function testReadableWithoutCondition(): void
    {
        $twig = <<<TWIG
            {{- readable -}}
        TWIG;
        $this->assertSame('', $this->twigRenderer->renderString($twig));
    }

    public function testReadableWithEmptyParam(): void
    {
        $twig = <<<TWIG
            {%- if '' is readable -%}readable{% else %}not readable{%- endif -%}
        TWIG;
        $this->assertSame('not readable', $this->twigRenderer->renderString($twig));
    }

    public function testReadableWithExistingAlias(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy.pdf' is readable -%}is readable{% else %}not readable{%- endif -%}
        TWIG;
        $this->assertSame('is readable', $this->twigRenderer->renderString($twig));
    }
    
    public function testReadableWithExistingAliasWithoutPermissions(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy-no-rights.pdf' is readable -%}is readable{% else %}not readable{%- endif -%}
        TWIG;
        $this->assertSame('not readable', $this->twigRenderer->renderString($twig));
    }
    
    public function testReadableWithNotExistingAlias(): void
    {
        $twig = <<<TWIG
            {%- if '@media/dummy-xyz.pdf' is readable -%}is readable{% else %}not readable{%- endif -%}
        TWIG;
        $this->assertSame('not readable', $this->twigRenderer->renderString($twig));
    }
}
