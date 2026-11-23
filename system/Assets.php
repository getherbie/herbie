<?php

declare(strict_types=1);

namespace herbie;

/**
 * @see: http://fuelphp.com/docs/classes/asset/usage.html
 * @see: http://docs.phalconphp.com/en/latest/reference/assets.html
 */
final class Assets
{
    private const int TYPE_CSS = 0;
    private const int TYPE_JS = 1;
    private static int $counter = 0;
    private static bool $sorted = false;
    private static array $published = [];
    private array $assets = [];
    private string $assetsDir = '/assets';
    private readonly string $assetsUrl;
    private readonly string $assetsPath;
    private int $refresh = 86400;
    private int $permissions = 0755;

    /** @param array<int, string> $themeFallbackPaths */
    public function __construct(
        private readonly Alias $alias,
        string $baseUrl,
        private readonly array $themeFallbackPaths = [],
    ) {
        $this->assetsPath = $this->alias->get('@web') . $this->assetsDir;
        $this->assetsUrl = str_leading_slash($baseUrl . $this->assetsDir);
    }

    /**
     * @param array|string $paths
     * TODO: Rename $raw for clarity. When true, $paths is treated as inline CSS content, not a file path/URL.
     */
    public function addCss($paths, array $attr = [], ?string $group = null, bool $raw = false, int $pos = 1): void
    {
        $paths = is_array($paths) ? $paths : [$paths];
        foreach ($paths as $path) {
            $this->addAsset(self::TYPE_CSS, $path, $attr, $group, $raw, $pos);
        }
    }

    private function addAsset(
        int $type,
        string $path,
        array $attr,
        ?string $group = null,
        bool $raw = false,
        int $pos = 1,
    ): void {
        if ($this->search($path)) {
            return;
        }
        $this->assets[] = [
            'type' => $type,
            'path' => $path,
            'group' => $group,
            'attr' => $attr,
            'raw' => $raw,
            'pos' => $pos,
            'counter' => ++self::$counter,
            'timestamp' => 0,
        ];
    }

    /**
     * @return bool|int
     */
    private function search(string $path)
    {
        foreach ($this->assets as $index => $asset) {
            if ($asset['path'] === $path) {
                return $index;
            }
        }
        return false;
    }

    /**
     * @param array|string $paths
     * TODO: Rename $raw for clarity. When true, $paths is treated as inline JavaScript content, not a file path/URL.
     */
    public function addJs($paths, array $attr = [], ?string $group = null, bool $raw = false, int $pos = 1): void
    {
        $paths = is_array($paths) ? $paths : [$paths];
        foreach ($paths as $path) {
            $this->addAsset(self::TYPE_JS, $path, $attr, $group, $raw, $pos);
        }
    }

    public function outputCss(?string $group = null, bool $addTimestamp = false): string
    {
        $this->sort();
        $this->publish();
        $return = '';
        foreach ($this->collect(self::TYPE_CSS, $group) as $asset) {
            if (empty($asset['raw'])) {
                $timestamp = $addTimestamp ? '?t=' . $asset['timestamp'] : '';
                $return .= sprintf(
                    '<link href="%s" type="text/css" rel="stylesheet"%s>',
                    $this->buildUrl($asset['path']) . $timestamp,
                    $this->buildAttribs($asset['attr']),
                );
            } else {
                $return .= sprintf(
                    '<style%s>%s</style>',
                    $this->buildAttribs($asset['attr']),
                    $asset['path'],
                );
            }
        }
        return $return;
    }

    private function sort(): void
    {
        if (!self::$sorted) {
            uasort($this->assets, function ($a, $b) {
                if ($a['pos'] === $b['pos']) {
                    if ($a['counter'] < $b['counter']) {
                        return -1;
                    }
                }
                if ($a['pos'] < $b['pos']) {
                    return -1;
                }
                return 1;
            });
            self::$sorted = true;
        }
    }

    private function publish(): void
    {
        foreach ($this->assets as $index => $asset) {
            if (!empty($asset['raw']) || str_starts_with($asset['path'], '//') || str_starts_with($asset['path'], 'http')) {
                continue;
            }

            $srcPath = $this->resolveSourcePath($asset['path']);
            $dstPath = $this->assetsPath . '/' . $this->removeAlias($asset['path']);
            if (isset(self::$published[$dstPath])) {
                continue;
            }

            $dstDir = dirname($dstPath);
            if (!is_dir($dstDir)) {
                mkdir($dstDir, $this->permissions, true);
            }
            $copy = false;
            if (is_file($dstPath)) {
                $delta = time() - file_mtime($dstPath);
                if ($delta > $this->refresh || file_mtime($srcPath) > file_mtime($dstPath)) {
                    $copy = true;
                }
            } else {
                $copy = true;
            }
            if ($copy) {
                copy($srcPath, $dstPath);
            }
            $this->assets[$index]['timestamp'] = file_mtime($dstPath);
            self::$published[$dstPath] = true;
        }
    }

    private function resolveSourcePath(string $path): string
    {
        $srcPath = $this->alias->get($path);
        if (is_file($srcPath) || !str_starts_with($path, '@theme/')) {
            return $srcPath;
        }

        $relative = substr($path, strlen('@theme/'));
        foreach ($this->themeFallbackPaths as $themePath) {
            $candidate = $themePath . '/' . $relative;
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        return $srcPath;
    }

    private function removeAlias(string $file): string
    {
        $parts = explode('/', $file);
        $alias = array_shift($parts);
        // Namespace theme assets by the active theme so switching themes cannot
        // reuse a published path that still holds another theme's file.
        if ($alias === '@theme') {
            return 'themes/' . basename($this->alias->get('@theme')) . '/' . implode('/', $parts);
        }
        return implode('/', $parts);
    }

    private function collect(int $type, ?string $group = null): array
    {
        $assets = [];
        foreach ($this->assets as $asset) {
            if (($asset['type'] === $type) && ($asset['group'] === $group)) {
                $assets[] = $asset;
            }
        }
        return $assets;
    }

    private function buildUrl(string $file): string
    {
        $url = $file;
        if (str_starts_with($file, '@')) {
            $trimed = $this->removeAlias($file);
            $url = $this->assetsUrl . '/' . $trimed;
        }
        return $url;
    }

    private function buildAttribs(array $attribs = []): string
    {
        $html = '';
        foreach ($attribs as $key => $value) {
            $html .= ' ' . $key . '="' . $value . '" ';
        }
        return trim($html);
    }

    public function outputJs(?string $group = null, bool $addTimestamp = false): string
    {
        $this->sort();
        $this->publish();
        $return = '';
        foreach ($this->collect(self::TYPE_JS, $group) as $asset) {
            if (empty($asset['raw'])) {
                $timestamp = $addTimestamp ? '?t=' . $asset['timestamp'] : '';
                $return .= sprintf(
                    '<script src="%s"%s></script>',
                    $this->buildUrl($asset['path']) . $timestamp,
                    $this->buildAttribs($asset['attr']),
                );
            } else {
                $return .= sprintf(
                    '<script%s>%s</script>',
                    $this->buildAttribs($asset['attr']),
                    $asset['path'],
                );
            }
        }
        return $return;
    }
}
