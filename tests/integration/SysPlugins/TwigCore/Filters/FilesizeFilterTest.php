<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins\TwigCore\Filters;

use Codeception\Test\Unit;
use herbie\Application;
use herbie\ApplicationPaths;
use herbie\TwigRenderer;
use TypeError;

final class FilesizeFilterTest extends Unit
{
    protected TwigRenderer $twigRenderer;

    public function testFilesizeWithWrongType(): void
    {
        $this->expectException(TypeError::class);
        $twig = '{{ "string"|format_size }}'; // string
        $this->twigRenderer->renderString($twig);
        $twig = '{{ {a:1}|format_size }}'; // object
        $this->twigRenderer->renderString($twig);
    }

    public function testFilesizeWithNegativeValue(): void
    {
        $twig = '{{ "-1"|format_size }}';
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame('0', $actual);
    }

    public function testFilesizeWithValidValues(): void
    {
        $this->assertSame('0', $this->twigRenderer->renderString('{{ "0"|format_size }}'));
        $this->assertSame('1 Byte', $this->twigRenderer->renderString('{{ "1"|format_size }}'));
        $this->assertSame('2 B', $this->twigRenderer->renderString('{{ "2"|format_size }}'));
        $this->assertSame('1024 B', $this->twigRenderer->renderString('{{ "1024"|format_size }}'));
        $this->assertSame('1 KB', $this->twigRenderer->renderString('{{ "1025"|format_size }}')); // KB (* 1024)
        $this->assertSame('1024 KB', $this->twigRenderer->renderString('{{ "1048576"|format_size }}'));
        $this->assertSame('1 MB', $this->twigRenderer->renderString('{{ "1048577"|format_size }}')); // MB (* 1024)
        $this->assertSame('1024 MB', $this->twigRenderer->renderString('{{ "1073741824"|format_size }}'));
        $this->assertSame('1 GB', $this->twigRenderer->renderString('{{ "1073741825"|format_size }}')); // GB (* 1024)
        $this->assertSame('1024 GB', $this->twigRenderer->renderString('{{ "1099511627776"|format_size }}'));
        $this->assertSame(
            '1 TB',
            $this->twigRenderer->renderString('{{ "1099511627777"|format_size }}')
        ); // TB (* 1024)
        $this->assertSame('1024 TB', $this->twigRenderer->renderString('{{ "1125899906842624"|format_size }}'));
        $this->assertSame(
            '1 PB',
            $this->twigRenderer->renderString('{{ "1125899906842625"|format_size }}')
        ); // TB (* 1024)
    }

    protected function _setUp(): void
    {
        $app = new Application(
            new ApplicationPaths(
                dirname(__DIR__, 5),
                dirname(__DIR__, 3) . '/Fixtures/site',
                dirname(__DIR__, 5) . '/vendor',
                dirname(__DIR__, 4) . '/_data/web'
            )
        );
        $app->getPluginManager()->init();
        $app->getTwigRenderer()->init();
        $this->twigRenderer = $app->getTwigRenderer();
    }
}
