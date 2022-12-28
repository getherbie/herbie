<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins\TwigCore\Filters;

use Codeception\Test\Unit;
use herbie\Application;
use herbie\ApplicationPaths;
use herbie\TwigRenderer;

final class SlugifyFilterTest extends Unit
{
    protected TwigRenderer $twigRenderer;

    public function testSlugify(): void
    {
        $actual = $this->twigRenderer->renderString('{{ "AEOU-ÄÈÖÜ_äèöü"|slugify }}');
        $this->assertSame('aeou-aeeoeue-aeeoeue', $actual);

        $actual = $this->twigRenderer->renderString('{{ ""|slugify }}');
        $this->assertSame('', $actual);
    }

    // that's enough, we don't want to test 3rd-party libraries

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
