<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Filters;

use herbie\Application;
use herbie\ApplicationPaths;
use herbie\TwigRenderer;

use function herbie\time_format;
use function herbie\time_from_string;

final class StrftimeFilterTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;

    protected function _setUp(): void
    {
        $app = new Application(new ApplicationPaths(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site',
            dirname(__DIR__, 5) . '/vendor',
            dirname(__DIR__, 4) . '/_data/web'
        ));
        $app->getPluginManager()->init();
        $app->getTwigRenderer()->init();
        $this->twigRenderer = $app->getTwigRenderer();
    }

    public function testStrftimeWithValidDates(): void
    {
        // timestamp as integer
        $this->assertSame(
            '12. September 2022',
            $this->twigRenderer->renderString('{{ 1662952416|format_date("%e. %B %Y") }}')
        );

        // timestamp as string
        $this->assertSame(
            '12. September 2022',
            $this->twigRenderer->renderString('{{ "1662952416"|format_date("%e. %B %Y") }}')
        );

        // iso-date
        $this->assertSame(
            '12. September 2022',
            $this->twigRenderer->renderString('{{ "2022-09-12"|format_date("%e. %B %Y") }}')
        );

        // empty string
        $this->assertSame(
            time_format("%e. %B %Y"),
            $this->twigRenderer->renderString('{{ ""|format_date("%e. %B %Y") }}')
        );

        // year with month (without day)
        $this->assertSame(
            time_format(" 1. September 2022"),
            $this->twigRenderer->renderString('{{ "2022-09"|format_date("%e. %B %Y") }}')
        );
    }

    public function testStrftimeWithWrongDates(): void
    {
        $this->assertSame(
            'invalid-date',
            $this->twigRenderer->renderString('{{ "invalid-date"|format_date("%e. %B %Y") }}')
        );

        $this->assertSame(
            time_format('%e. %B %Y', time_from_string('1970-01-01')),
            $this->twigRenderer->renderString('{{ 2000|format_date("%e. %B %Y") }}')
        );

        $this->assertSame(
            '2000-13-32',
            $this->twigRenderer->renderString('{{ "2000-13-32"|format_date("%e. %B %Y") }}')
        );

        $this->assertSame(
            time_format('%e. %B %Y', time_from_string('2000-12-31')), // quite random
            $this->twigRenderer->renderString('{{ "20000-12-31"|format_date("%e. %B %Y") }}')
        );

        $this->assertSame(
            time_format("%e. %B %Y"),
            $this->twigRenderer->renderString('{{ false|format_date("%e. %B %Y") }}')
        );

        $this->assertSame(
            time_format('%e. %B %Y', time_from_string('1970-01-01')),
            $this->twigRenderer->renderString('{{ true|format_date("%e. %B %Y") }}')
        );
    }
}
