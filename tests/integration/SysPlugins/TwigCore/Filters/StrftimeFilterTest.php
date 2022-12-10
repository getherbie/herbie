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
}
