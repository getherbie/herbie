<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins;

use Codeception\Test\Unit;
use herbie\Application;
use herbie\ApplicationPaths;

final class LocalPluginDiscoveryTest extends Unit
{
    public function testLocalPluginsAreDiscoveredInExtendPlugins(): void
    {
        $app = new Application(new ApplicationPaths(
            dirname(__DIR__, 3),
            dirname(__DIR__, 2) . '/_data/site',
            dirname(__DIR__, 3) . '/vendor',
            dirname(__DIR__, 2) . '/_data/web',
        ));

        $plugins = $app->getConfig()->getAsArray('plugins');

        $this->assertSame('local', $plugins['dummy2']['location']);
        $this->assertSame('system', $plugins['markdown']['location']);
    }
}
