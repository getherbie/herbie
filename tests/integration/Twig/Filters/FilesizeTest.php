<?php

declare(strict_types=1);

namespace unit\Twig\Filters;

use herbie\Application;
use herbie\TwigRenderer;
use TypeError;

final class FilesizeTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;

    protected function _setUp(): void
    {
        $app = new Application(dirname(__DIR__, 2) . '/Fixtures/site', dirname(__DIR__, 4) . '/vendor');
        ($this->twigRenderer = $app->getTwigRenderer())->init();
    }

    public function testFilesizeWithWrongType(): void
    {
        $this->expectException(TypeError::class);
        $twig = '{{ "string"|filesize }}'; // string
        $this->twigRenderer->renderString($twig);
        $twig = '{{ {a:1}|filesize }}'; // object
        $this->twigRenderer->renderString($twig);
    }

    public function testFilesizeWithNegativeValue(): void
    {
        $twig = '{{ "-1"|filesize }}';
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame('0', $actual);
    }

    public function testFilesizeWithValidValues(): void
    {
        $this->assertSame('0', $this->twigRenderer->renderString('{{ "0"|filesize }}'));
        $this->assertSame('1 Byte', $this->twigRenderer->renderString('{{ "1"|filesize }}'));
        $this->assertSame('2 B', $this->twigRenderer->renderString('{{ "2"|filesize }}'));
        $this->assertSame('1024 B', $this->twigRenderer->renderString('{{ "1024"|filesize }}'));
        $this->assertSame('1 KB', $this->twigRenderer->renderString('{{ "1025"|filesize }}')); // KB (* 1024)
        $this->assertSame('1024 KB', $this->twigRenderer->renderString('{{ "1048576"|filesize }}'));
        $this->assertSame('1 MB', $this->twigRenderer->renderString('{{ "1048577"|filesize }}')); // MB (* 1024)
        $this->assertSame('1024 MB', $this->twigRenderer->renderString('{{ "1073741824"|filesize }}'));
        $this->assertSame('1 GB', $this->twigRenderer->renderString('{{ "1073741825"|filesize }}')); // GB (* 1024)
        $this->assertSame('1024 GB', $this->twigRenderer->renderString('{{ "1099511627776"|filesize }}'));
        $this->assertSame('1 TB', $this->twigRenderer->renderString('{{ "1099511627777"|filesize }}')); // TB (* 1024)
        $this->assertSame('1024 TB', $this->twigRenderer->renderString('{{ "1125899906842624"|filesize }}'));
        $this->assertSame('1 PB', $this->twigRenderer->renderString('{{ "1125899906842625"|filesize }}')); // TB (* 1024)
    }
}
