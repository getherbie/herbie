<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Config;
use herbie\EventManager;
use herbie\PluginManager;
use herbie\Translator;
use Psr\Container\ContainerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
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

    public function testResolvedPluginsFollowTheOrderOfTheListNotTheirLocation(): void
    {
        $manager = $this->createManager([
            'enabledPlugins' => 'local1,system1,composer1,system2',
            'plugins' => [
                'system1' => $this->pluginConfig('system1', 'system'),
                'system2' => $this->pluginConfig('system2', 'system'),
                'composer1' => $this->pluginConfig('composer1', 'composer'),
                'local1' => $this->pluginConfig('local1', 'local'),
            ],
        ]);

        $keys = array_map(fn($plugin) => $plugin->getKey(), $manager->resolveInstallablePlugins());

        $this->assertSame(['local1', 'system1', 'composer1', 'system2'], $keys);
    }

    public function testResolvedPluginKeepsItsConfiguredValues(): void
    {
        $manager = $this->createManager([
            'enabledPlugins' => 'foo',
            'plugins' => ['foo' => $this->pluginConfig('foo', 'composer')],
        ]);

        $plugins = $manager->resolveInstallablePlugins();

        $this->assertCount(1, $plugins);
        $this->assertSame('foo', $plugins[0]->getKey());
        $this->assertSame('/path/to/foo', $plugins[0]->getPath());
        $this->assertSame('herbie\tests\unit\FooPlugin', $plugins[0]->getClassName());
        $this->assertSame('composer', $plugins[0]->getType());
    }

    public function testUnknownPluginIsSkippedAndLogged(): void
    {
        $logger = $this->createLogger();
        $manager = $this->createManager([
            'enabledPlugins' => 'unknown,foo',
            'plugins' => ['foo' => $this->pluginConfig('foo', 'system')],
        ], $logger);

        $keys = array_map(fn($plugin) => $plugin->getKey(), $manager->resolveInstallablePlugins());

        $this->assertSame(['foo'], $keys);
        $this->assertCount(1, $logger->warnings);
        $this->assertStringContainsString('unknown', $logger->warnings[0]);
    }

    public function testPluginWithIncompleteConfigIsSkippedAndLogged(): void
    {
        $logger = $this->createLogger();
        $manager = $this->createManager([
            'enabledPlugins' => 'broken',
            'plugins' => ['broken' => ['pluginName' => 'broken', 'location' => 'local']],
        ], $logger);

        $this->assertSame([], $manager->resolveInstallablePlugins());
        $this->assertCount(1, $logger->warnings);
        $this->assertStringContainsString('broken', $logger->warnings[0]);
    }

    public function testNoPluginIsResolvedWithoutEnabledPlugins(): void
    {
        $manager = $this->createManager([
            'enabledPlugins' => '',
            'plugins' => ['foo' => $this->pluginConfig('foo', 'system')],
        ]);

        $this->assertSame([], $manager->resolveInstallablePlugins());
    }

    /**
     * @return array<string, string>
     */
    private function pluginConfig(string $name, string $location): array
    {
        return [
            'location' => $location,
            'pluginName' => $name,
            'pluginPath' => '/path/to/' . $name,
            'pluginClass' => 'herbie\tests\unit\FooPlugin',
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createManager(array $config = ['enabledPlugins' => ''], ?LoggerInterface $logger = null): PluginManager
    {
        return new PluginManager(
            new Config($config),
            new EventManager(),
            new Translator('de'),
            $logger ?? new NullLogger(),
            $this->createContainer(),
        );
    }

    private function createLogger(): AbstractLogger
    {
        return new class extends AbstractLogger {
            /** @var string[] */
            public array $warnings = [];

            public function log($level, $message, array $context = [])
            {
                if ($level === LogLevel::WARNING) {
                    $this->warnings[] = (string)$message;
                }
            }
        };
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
