<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Config;
use herbie\EventManager;
use herbie\Plugin;
use herbie\PluginManager;
use herbie\SystemException;
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

        $keys = array_map(fn(\herbie\InstallablePlugin $plugin): string => $plugin->getKey(), $manager->resolveInstallablePlugins());

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

        $keys = array_map(fn(\herbie\InstallablePlugin $plugin): string => $plugin->getKey(), $manager->resolveInstallablePlugins());

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

    public function testPluginWithInstalledRequiredPackageIsResolved(): void
    {
        $manager = $this->createManager([
            'enabledPlugins' => 'foo',
            'plugins' => ['foo' => $this->pluginConfig('foo', 'system', PluginWithInstalledPackage::class)],
        ]);

        $this->assertCount(1, $manager->resolveInstallablePlugins());
    }

    public function testPluginWithMissingRequiredPackageThrowsException(): void
    {
        $manager = $this->createManager([
            'enabledPlugins' => 'foo',
            'plugins' => ['foo' => $this->pluginConfig('foo', 'system', PluginWithMissingPackage::class)],
        ]);

        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('composer require does-not/exist');

        $manager->resolveInstallablePlugins();
    }

    public function testPluginWithWrongVersionOfRequiredPackageThrowsException(): void
    {
        $manager = $this->createManager([
            'enabledPlugins' => 'foo',
            'plugins' => ['foo' => $this->pluginConfig('foo', 'system', PluginWithWrongPackageVersion::class)],
        ]);

        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('psr/log in version ^9.0');

        $manager->resolveInstallablePlugins();
    }

    public function testRequiredPackagesOfPluginsThatAreNotEnabledAreNotChecked(): void
    {
        $manager = $this->createManager([
            'enabledPlugins' => 'bar',
            'plugins' => [
                'foo' => $this->pluginConfig('foo', 'system', PluginWithMissingPackage::class),
                'bar' => $this->pluginConfig('bar', 'system'),
            ],
        ]);

        $keys = array_map(fn(\herbie\InstallablePlugin $plugin): string => $plugin->getKey(), $manager->resolveInstallablePlugins());

        $this->assertSame(['bar'], $keys);
    }

    /**
     * @return array<string, string>
     */
    private function pluginConfig(string $name, string $location, string $class = 'herbie\tests\unit\FooPlugin'): array
    {
        return [
            'location' => $location,
            'pluginName' => $name,
            'pluginPath' => '/path/to/' . $name,
            'pluginClass' => $class,
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

            public function log($level, $message, array $context = []): void
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

#[Plugin('installed', requires: ['psr/log' => '^1.1'])]
final class PluginWithInstalledPackage {}

#[Plugin('missing', requires: ['does-not/exist'])]
final class PluginWithMissingPackage {}

#[Plugin('wrong-version', requires: ['psr/log' => '^9.0'])]
final class PluginWithWrongPackageVersion {}
