<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Filters;

use herbie\Application;
use herbie\ApplicationPaths;
use herbie\TwigRenderer;

final class SlugifyFilterTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;

    protected function _setUp(): void
    {
        $app = new Application(new ApplicationPaths(dirname(__DIR__, 5), dirname(__DIR__, 3) . '/Fixtures/site'));
        $app->getPluginManager()->init();
        $app->getTwigRenderer()->init();
        $this->twigRenderer = $app->getTwigRenderer();
    }

    // that's enough, we don't want to test 3rd-party libraries
    public function testSlugify(): void
    {
        $actual = $this->twigRenderer->renderString('{{ "AEOU-ÄÈÖÜ_äèöü"|slugify }}');
        $this->assertSame('aeou-aeeoeue-aeeoeue', $actual);

        $actual = $this->twigRenderer->renderString('{{ ""|slugify }}');
        $this->assertSame('', $actual);
    }
}
