<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Config;
use herbie\EventManager;
use herbie\PluginManager;
use herbie\Translator;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * Only the parts that do not require a fully wired container are covered here. Booting the
 * plugin manager loads every enabled system plugin, which the acceptance tests cover.
 */
final class PluginManagerTest extends Unit
{
    public function testManagerIsNotInitializedAfterConstruction(): void
    {
        $manager = $this->createManager();

        $this->assertFalse($manager->isInitialized());
    }

    public function testAllCollectionsAreEmptyBeforeInit(): void
    {
        $manager = $this->createManager();

        $this->assertSame([], $manager->getLoadedPlugins());
        $this->assertSame([], $manager->getConsoleCommands());
        $this->assertSame([], $manager->getApplicationMiddlewares());
        $this->assertSame([], $manager->getRouteMiddlewares());
        $this->assertSame([], $manager->getPluginPaths());
    }

    private function createManager(): PluginManager
    {
        return new PluginManager(
            new Config(['enabledSysPlugins' => '', 'enabledPlugins' => '']),
            new EventManager(),
            new Translator('de'),
            new NullLogger(),
            $this->createContainer()
        );
    }

    private function createContainer(): ContainerInterface
    {
        return new class implements ContainerInterface {
            public function get(string $id): mixed
            {
                throw new \RuntimeException('The container is not used in these tests.');
            }

            public function has(string $id): bool
            {
                return false;
            }
        };
    }
}
