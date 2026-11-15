<?php

declare(strict_types=1);

namespace herbie;

use Composer\InstalledVersions;

/**
 * Finds the plugins of the three sources "system", "composer" and "local".
 *
 * A plugin is a directory with a class marked with the #[Plugin('name')] attribute.
 * The class is found by scanning the PHP files of the directory with the tokenizer, no code is executed.
 * An optional config.php in the plugin directory returns the default values of the plugin.
 *
 * The result is cached in a file. The cache is renewed if a plugin directory or a Composer plugin is added,
 * removed or updated. It is not renewed if only the content of a file changes.
 */
final class PluginDiscovery
{
    private const string ATTRIBUTE = Plugin::class;

    private const string CONFIG_FILE = 'config.php';

    private const string COMPOSER_PACKAGE_TYPE = 'herbie-plugin';

    /**
     * @param string|null $cacheFile Path to the cache file, null disables the cache
     */
    public function __construct(private ?string $cacheFile = null) {}

    /**
     * Returns the config of all plugins by plugin name.
     * If a name exists more than once, the order system, composer, local decides, the last one wins.
     *
     * @return array<string, array<string, mixed>>
     */
    public function discover(string $systemDir, string $localDir, ?callable $processor = null): array
    {
        $sources = $this->getSources($systemDir, $localDir);
        $fingerprint = md5(serialize($sources));

        $entries = $this->readCache($fingerprint);
        if ($entries === null) {
            $entries = $this->scanSources($sources);
            $this->writeCache($fingerprint, $entries);
        }

        $plugins = [];
        foreach ($entries as $entry) {
            $defaults = $processor ? (array)$processor($entry['defaults']) : $entry['defaults'];
            $name = $entry['info']['pluginName'];
            $plugins[$name] = array_replace_recursive($plugins[$name] ?? [], array_merge($defaults, $entry['info']));
        }

        return $plugins;
    }

    /**
     * @return array<int, array{string, string, string}> The location, directory and version of each plugin directory
     */
    private function getSources(string $systemDir, string $localDir): array
    {
        $sources = [];

        foreach ($this->getSubDirectories($systemDir) as $dir) {
            $sources[] = ['system', $dir, ''];
        }

        foreach (array_unique(InstalledVersions::getInstalledPackagesByType(self::COMPOSER_PACKAGE_TYPE)) as $package) {
            $installPath = InstalledVersions::getInstallPath($package);
            $path = $installPath === null ? false : realpath($installPath);
            if ($path === false) {
                continue;
            }
            $sources[] = ['composer', $path, (string)InstalledVersions::getReference($package)];
        }

        foreach ($this->getSubDirectories($localDir) as $dir) {
            $sources[] = ['local', $dir, ''];
        }

        return $sources;
    }

    /**
     * @return string[]
     */
    private function getSubDirectories(string $dir): array
    {
        $dir = str_untrailing_slash($dir);
        if ($dir === '') {
            return [];
        }
        return glob($dir . '/*', GLOB_ONLYDIR) ?: [];
    }

    /**
     * @param array<int, array{string, string, string}> $sources
     * @return array<int, array{info: array<string, string>, defaults: array<string, mixed>}>
     */
    private function scanSources(array $sources): array
    {
        $entries = [];
        foreach ($sources as [$location, $dir]) {
            foreach (glob($dir . '/*.php') ?: [] as $file) {
                if (basename($file) === self::CONFIG_FILE) {
                    continue;
                }
                $plugin = $this->findPluginClass($file);
                if ($plugin === null) {
                    continue;
                }
                $configFile = $dir . '/' . self::CONFIG_FILE;
                $entries[] = [
                    'info' => [
                        'location' => $location,
                        'pluginName' => $plugin['name'],
                        'pluginClass' => $plugin['class'],
                        'pluginFile' => $file,
                        'pluginPath' => $dir,
                    ],
                    'defaults' => is_file($configFile) ? load_php_config($configFile) : [],
                ];
                // one plugin per directory
                break;
            }
        }
        return $entries;
    }

    /**
     * @return array<int, array{info: array<string, string>, defaults: array<string, mixed>}>|null
     */
    private function readCache(string $fingerprint): ?array
    {
        if ($this->cacheFile === null || !is_file($this->cacheFile)) {
            return null;
        }
        $cached = @include $this->cacheFile;
        if (!is_array($cached) || ($cached['fingerprint'] ?? null) !== $fingerprint || !is_array($cached['entries'] ?? null)) {
            return null;
        }
        return $cached['entries'];
    }

    /**
     * @param array<int, array{info: array<string, string>, defaults: array<string, mixed>}> $entries
     */
    private function writeCache(string $fingerprint, array $entries): void
    {
        if ($this->cacheFile === null) {
            return;
        }
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }
        $code = '<?php return ' . var_export(['fingerprint' => $fingerprint, 'entries' => $entries], true) . ";\n";
        $tmpFile = $this->cacheFile . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmpFile, $code) === false) {
            return;
        }
        if (!@rename($tmpFile, $this->cacheFile)) {
            @unlink($tmpFile);
        }
    }

    /**
     * Searches a PHP file for a class with the #[Plugin] attribute.
     *
     * @return array{class: string, name: string}|null
     */
    private function findPluginClass(string $file): ?array
    {
        $code = @file_get_contents($file);
        if ($code === false || !str_contains($code, '#[')) {
            return null;
        }

        $ignored = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];
        $tokens = array_values(array_filter(
            token_get_all($code),
            fn($token) => !is_array($token) || !in_array($token[0], $ignored, true),
        ));
        $count = count($tokens);

        $namespace = '';
        $imports = [];
        $pluginAttribute = null;

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (!is_array($token)) {
                continue;
            }
            switch ($token[0]) {
                case T_NAMESPACE:
                    [$namespace, $i] = $this->readName($tokens, $i + 1);
                    break;
                case T_USE:
                    [$name, $i] = $this->readName($tokens, $i + 1);
                    if ($name === '') {
                        break;
                    }
                    $next = $tokens[$i + 1] ?? null;
                    $aliasToken = $tokens[$i + 2] ?? null;
                    if (is_array($next) && $next[0] === T_AS && is_array($aliasToken)) {
                        $alias = $aliasToken[1];
                        $i += 2;
                    } else {
                        $alias = substr((string)strrchr('\\' . $name, '\\'), 1);
                    }
                    $imports[strtolower($alias)] = ltrim($name, '\\');
                    break;
                case T_ATTRIBUTE:
                    [$attribute, $i] = $this->readAttributes($tokens, $i + 1, $namespace, $imports);
                    if ($attribute !== null) {
                        $pluginAttribute = $attribute;
                    }
                    break;
                case T_INTERFACE:
                case T_TRAIT:
                case T_ENUM:
                    $pluginAttribute = null;
                    break;
                case T_CLASS:
                    $previous = $tokens[$i - 1] ?? null;
                    $className = $tokens[$i + 1] ?? null;
                    if (
                        (is_array($previous) && in_array($previous[0], [T_DOUBLE_COLON, T_NEW], true))
                        || !is_array($className)
                        || $className[0] !== T_STRING
                    ) {
                        break;
                    }
                    if ($pluginAttribute !== null) {
                        return [
                            'class' => ltrim($namespace . '\\' . $className[1], '\\'),
                            'name' => $pluginAttribute['name'],
                        ];
                    }
                    break;
            }
        }

        return null;
    }

    /**
     * Reads a name like Foo, Foo\Bar or \Foo\Bar beginning at the given position.
     *
     * @param array<int, array{int, string, int}|string> $tokens
     * @return array{string, int} The name and the position of its last token, the given position minus one if no name
     */
    private function readName(array $tokens, int $position): array
    {
        $kinds = [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE, T_NS_SEPARATOR];
        $name = '';
        $i = $position;
        while (isset($tokens[$i]) && is_array($tokens[$i]) && in_array($tokens[$i][0], $kinds, true)) {
            $name .= $tokens[$i][1];
            $i++;
        }
        return [$name, $i - 1];
    }

    /**
     * Reads the attributes of an attribute group, the position is the one after "#[".
     * Returns the name argument of the Plugin attribute, if the group contains it with a string as argument.
     *
     * @param array<int, array{int, string, int}|string> $tokens
     * @param array<string, string> $imports
     * @return array{array{name: string}|null, int} The attribute and the position of the closing bracket
     */
    private function readAttributes(array $tokens, int $position, string $namespace, array $imports): array
    {
        $found = null;
        $name = '';
        $argument = null;
        $parentheses = 0;
        $brackets = 0;
        $count = count($tokens);

        for ($i = $position; $i < $count; $i++) {
            $token = $tokens[$i];
            $endOfAttribute = false;
            $endOfGroup = false;

            if ($token === '(') {
                $parentheses++;
            } elseif ($token === ')') {
                $parentheses--;
            } elseif ($token === '[') {
                $brackets++;
            } elseif ($token === ']') {
                if ($brackets === 0) {
                    $endOfAttribute = true;
                    $endOfGroup = true;
                } else {
                    $brackets--;
                }
            } elseif ($token === ',' && $parentheses === 0 && $brackets === 0) {
                $endOfAttribute = true;
            } elseif (is_array($token)) {
                $nameKinds = [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE];
                if ($parentheses === 0 && in_array($token[0], $nameKinds, true)) {
                    $name .= $token[1];
                } elseif ($parentheses === 1 && $argument === null && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
                    $argument = substr($token[1], 1, -1);
                }
            }

            if ($endOfAttribute) {
                if (
                    $name !== ''
                    && $argument !== null
                    && strcasecmp($this->resolveName($name, $namespace, $imports), self::ATTRIBUTE) === 0
                ) {
                    $found = ['name' => $argument];
                }
                $name = '';
                $argument = null;
            }
            if ($endOfGroup) {
                return [$found, $i];
            }
        }

        return [$found, $count - 1];
    }

    /**
     * @param array<string, string> $imports
     */
    private function resolveName(string $name, string $namespace, array $imports): string
    {
        if ($name[0] === '\\') {
            return ltrim($name, '\\');
        }
        $parts = explode('\\', $name, 2);
        $first = strtolower($parts[0]);
        if (isset($imports[$first])) {
            return $imports[$first] . (isset($parts[1]) ? '\\' . $parts[1] : '');
        }
        return ltrim($namespace . '\\' . $name, '\\');
    }
}
