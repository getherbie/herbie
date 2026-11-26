<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\PluginDiscovery;

/**
 * The plugin files only contain code to look at, they are never loaded.
 */
final class PluginDiscoveryTest extends Unit
{
    private string $root;

    protected function _before(): void
    {
        $this->root = sys_get_temp_dir() . '/herbie-plugin-discovery-' . bin2hex(random_bytes(4));
        mkdir($this->root . '/system', 0755, true);
        mkdir($this->root . '/local', 0755, true);
    }

    protected function _after(): void
    {
        $this->removeDirectory($this->root);
    }

    public function testFindsPluginWithNameFromAttribute(): void
    {
        $this->writePlugin('system', 'foo', 'FooPlugin.php', <<<'PHP'
            <?php

            namespace my\ns;

            use herbie\Plugin;

            #[Plugin('bar')]
            final class FooPlugin {}
            PHP);

        $plugins = $this->discover();

        $this->assertArrayHasKey('bar', $plugins);
        $this->assertArrayNotHasKey('foo', $plugins);
        $this->assertSame('system', $plugins['bar']['location']);
        $this->assertSame('bar', $plugins['bar']['pluginName']);
        $this->assertSame('my\ns\FooPlugin', $plugins['bar']['pluginClass']);
        $this->assertSame($this->root . '/system/foo', $plugins['bar']['pluginPath']);
        $this->assertSame($this->root . '/system/foo/FooPlugin.php', $plugins['bar']['pluginFile']);
    }

    public function testIgnoresAttributeWithoutName(): void
    {
        $this->writePlugin('system', 'foo', 'FooPlugin.php', <<<'PHP'
            <?php

            namespace my\ns;

            use herbie\Plugin;

            #[Plugin]
            final class FooPlugin {}
            PHP);

        $this->assertSame([], $this->discover());
    }

    public function testFindsPluginInGlobalNamespace(): void
    {
        $this->writePlugin('local', 'foo', 'plugin.php', <<<'PHP'
            <?php

            use herbie\Plugin;

            #[Plugin('foo')]
            class MyPlugin {}
            PHP);

        $plugins = $this->discover();

        $this->assertSame('MyPlugin', $plugins['foo']['pluginClass']);
        $this->assertSame('local', $plugins['foo']['location']);
    }

    public function testResolvesFullyQualifiedAndAliasedAttributeNames(): void
    {
        $this->writePlugin('system', 'a', 'A.php', <<<'PHP'
            <?php

            namespace x;

            #[\herbie\Plugin('a')]
            final class A {}
            PHP);
        $this->writePlugin('system', 'b', 'B.php', <<<'PHP'
            <?php

            namespace x;

            use herbie\Plugin as Name;

            #[Name('b')]
            final class B {}
            PHP);
        $this->writePlugin('system', 'c', 'C.php', <<<'PHP'
            <?php

            namespace herbie;

            #[Plugin('c')]
            final class C {}
            PHP);

        $plugins = $this->discover();

        $this->assertSame('x\A', $plugins['a']['pluginClass']);
        $this->assertSame('x\B', $plugins['b']['pluginClass']);
        $this->assertSame('herbie\C', $plugins['c']['pluginClass']);
    }

    public function testFindsAttributeInGroupWithOtherAttributes(): void
    {
        $this->writePlugin('system', 'foo', 'FooPlugin.php', <<<'PHP'
            <?php

            namespace x;

            use herbie\Plugin;

            #[Deprecated('some, text'), Plugin('foo')]
            #[Other(['a' => 1])]
            final class FooPlugin {}
            PHP);

        $this->assertArrayHasKey('foo', $this->discover());
    }

    public function testFindsNameWhenManifestHasRequires(): void
    {
        $this->writePlugin('system', 'foo', 'FooPlugin.php', <<<'PHP'
            <?php

            namespace x;

            use herbie\Plugin;

            #[Plugin('foo', requires: ['vendor/package' => '^1.0', 'other/package'])]
            final class FooPlugin {}
            PHP);

        $this->assertSame('x\FooPlugin', $this->discover()['foo']['pluginClass']);
    }

    public function testIgnoresOtherAttributesAndClassesWithoutAttribute(): void
    {
        $this->writePlugin('system', 'foo', 'Plain.php', <<<'PHP'
            <?php

            namespace x;

            use Other\Plugin;

            #[Plugin('foo')]
            final class Plain {}
            PHP);
        $this->writePlugin('system', 'bar', 'Bar.php', <<<'PHP'
            <?php

            namespace x;

            // Plugin is only mentioned here
            final class Bar
            {
                public function name(): string
                {
                    return self::class;
                }
            }
            PHP);

        $this->assertSame([], $this->discover());
    }

    public function testIgnoresClassNameConstantAndAnonymousClasses(): void
    {
        $this->writePlugin('system', 'foo', 'FooPlugin.php', <<<'PHP'
            <?php

            namespace x;

            use herbie\Plugin;

            $name = Foo::class;
            $anonymous = new class {};

            #[Plugin('foo')]
            final class FooPlugin {}
            PHP);

        $this->assertSame('x\FooPlugin', $this->discover()['foo']['pluginClass']);
    }

    public function testDefaultsFromConfigFileAreMergedAndCannotOverridePluginInfo(): void
    {
        $this->writePlugin('system', 'foo', 'FooPlugin.php', <<<'PHP'
            <?php

            use herbie\Plugin;

            #[Plugin('foo')]
            final class FooPlugin {}
            PHP);
        file_put_contents($this->root . '/system/foo/config.php', <<<'PHP'
            <?php

            return [
                'pluginName' => 'other',
                'config' => ['path' => 'APP_PATH/foo'],
            ];
            PHP);

        $processor = fn(array $data): array => [
            'pluginName' => $data['pluginName'],
            'config' => ['path' => str_replace('APP_PATH', '/app', $data['config']['path'])],
        ];
        $plugins = $this->discover(null, $processor);

        $this->assertSame('foo', $plugins['foo']['pluginName']);
        $this->assertSame(['path' => '/app/foo'], $plugins['foo']['config']);
    }

    public function testLocalPluginOverridesSystemPluginWithSameName(): void
    {
        $this->writePlugin('system', 'foo', 'FooPlugin.php', <<<'PHP'
            <?php

            use herbie\Plugin;

            #[Plugin('foo')]
            final class SystemFoo {}
            PHP);
        $this->writePlugin('local', 'foo', 'FooPlugin.php', <<<'PHP'
            <?php

            use herbie\Plugin;

            #[Plugin('foo')]
            final class LocalFoo {}
            PHP);

        $plugins = $this->discover();

        $this->assertSame('local', $plugins['foo']['location']);
        $this->assertSame('LocalFoo', $plugins['foo']['pluginClass']);
    }

    public function testWithoutCacheChangesAreFoundImmediately(): void
    {
        $this->writePlugin('system', 'foo', 'FooPlugin.php', $this->pluginCode('FooV1'));
        $this->assertSame('FooV1', $this->discover()['foo']['pluginClass']);

        $this->writePlugin('system', 'foo', 'FooPlugin.php', $this->pluginCode('FooV2'));
        $this->assertSame('FooV2', $this->discover()['foo']['pluginClass']);
    }

    public function testCacheIsUsedUntilAPluginDirectoryIsAdded(): void
    {
        $cacheFile = $this->root . '/cache/plugins.php';
        $this->writePlugin('system', 'foo', 'FooPlugin.php', $this->pluginCode('FooV1'));
        $this->assertSame('FooV1', $this->discover($cacheFile)['foo']['pluginClass']);
        $this->assertFileExists($cacheFile);

        // a changed file is not noticed
        $this->writePlugin('system', 'foo', 'FooPlugin.php', $this->pluginCode('FooV2'));
        $this->assertSame('FooV1', $this->discover($cacheFile)['foo']['pluginClass']);

        // a new plugin directory renews the cache
        $this->writePlugin('system', 'bar', 'BarPlugin.php', $this->pluginCode('BarV1', 'bar'));
        $plugins = $this->discover($cacheFile);
        $this->assertSame('FooV2', $plugins['foo']['pluginClass']);
        $this->assertSame('BarV1', $plugins['bar']['pluginClass']);
    }

    public function testCachedDefaultsAreProcessedOnEveryCall(): void
    {
        $cacheFile = $this->root . '/cache/plugins.php';
        $this->writePlugin('system', 'foo', 'FooPlugin.php', $this->pluginCode('FooV1'));
        file_put_contents($this->root . '/system/foo/config.php', "<?php\n\nreturn ['url' => 'WEB_URL/foo'];\n");

        $first = $this->discover($cacheFile, fn(array $data): array => ['url' => str_replace('WEB_URL', 'https://a.test', $data['url'])]);
        $second = $this->discover($cacheFile, fn(array $data): array => ['url' => str_replace('WEB_URL', 'https://b.test', $data['url'])]);

        $this->assertSame('https://a.test/foo', $first['foo']['url']);
        $this->assertSame('https://b.test/foo', $second['foo']['url']);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function discover(?string $cacheFile = null, ?callable $processor = null): array
    {
        $plugins = (new PluginDiscovery($cacheFile))
            ->discover($this->root . '/system', $this->root . '/local', $processor);

        // ignore Composer plugins that are installed in the environment running the tests
        return array_filter($plugins, fn(array $plugin): bool => $plugin['location'] !== 'composer');
    }

    private function pluginCode(string $class, string $name = 'foo'): string
    {
        return "<?php\n\nuse herbie\\Plugin;\n\n#[Plugin('" . $name . "')]\nfinal class " . $class . " {}\n";
    }

    private function writePlugin(string $source, string $dir, string $file, string $code): void
    {
        $path = $this->root . '/' . $source . '/' . $dir;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        file_put_contents($path . '/' . $file, $code . "\n");
    }

    private function removeDirectory(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $path) {
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
