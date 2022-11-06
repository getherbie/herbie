<?php

declare(strict_types=1);

namespace herbie;

/**
 * @see: http://fuelphp.com/docs/classes/asset/usage.html
 * @see: http://docs.phalconphp.com/en/latest/reference/assets.html
 */
final class Assets
{
    private const TYPE_CSS = 0;
    private const TYPE_JS = 1;

    private Alias $alias;
    private array $assets = [];
    private string $assetsDir = '/assets';
    private string $assetsUrl;
    private string $assetsPath;
    private int $refresh = 86400;
    private int $permissions = 0755;
    private static int $counter = 0;
    private static bool $sorted = false;
    private static array $published = [];

    public function __construct(Alias $alias, string $baseUrl)
    {
        $this->alias = $alias;
        $this->assetsPath = $alias->get('@web') . $this->assetsDir;
        $this->assetsUrl = str_leading_slash($baseUrl . $this->assetsDir);
    }

    /**
     * @param array|string $paths
     */
    public function addCss($paths, array $attr = [], ?string $group = null, bool $raw = false, int $pos = 1): void
    {
        $paths = is_array($paths) ? $paths : [$paths];
        foreach ($paths as $path) {
            $this->addAsset(self::TYPE_CSS, $path, $attr, $group, $raw, $pos);
        }
    }

    /**
     * @param array|string $paths
     */
    public function addJs($paths, array $attr = [], ?string $group = null, bool $raw = false, int $pos = 1): void
    {
        $paths = is_array($paths) ? $paths : [$paths];
        foreach ($paths as $path) {
            $this->addAsset(self::TYPE_JS, $path, $attr, $group, $raw, $pos);
        }
    }

    public function outputCss(?string $group = null): string
    {
        $this->sort();
        $this->publish();
        $return = '';
        foreach ($this->collect(self::TYPE_CSS, $group) as $asset) {
            if (empty($asset['raw'])) {
                $href = $this->buildUrl($asset['path']);
                $return .= sprintf('<link href="%s" type="text/css" rel="stylesheet">', $href);
            } else {
                $return .= sprintf('<style>%s</style>', $asset['path']);
            }
        }
        return $return;
    }

    public function outputJs(?string $group = null): string
    {
        $this->sort();
        $this->publish();
        $return = '';
        foreach ($this->collect(self::TYPE_JS, $group) as $asset) {
            if (empty($asset['raw'])) {
                $href = $this->buildUrl($asset['path']);
                $return .= sprintf('<script src="%s"></script>', $href);
            } else {
                $return .= sprintf('<script>%s</script>', $asset['path']);
            }
        }
        return $return;
    }

    private function addAsset(
        int $type,
        string $path,
        array $attr,
        ?string $group = null,
        bool $raw = false,
        int $pos = 1
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
            'counter' => ++self::$counter
        ];
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

    private function publish(): void
    {
        foreach ($this->assets as $asset) {
            if (!empty($asset['raw']) || 0 === strpos($asset['path'], '//') || 0 === strpos($asset['path'], 'http')) {
                continue;
            }

            $srcPath = $this->alias->get($asset['path']);
            if (isset(self::$published[$srcPath])) {
                continue;
            }

            $dstPath = $this->assetsPath . '/' . $this->removeAlias($asset['path']);
            $dstDir = dirname($dstPath);
            if (!is_dir($dstDir)) {
                mkdir($dstDir, $this->permissions, true);
            }
            $copy = false;
            if (is_file($dstPath)) {
                $delta = time() - file_mtime($dstPath);
                if ($delta > $this->refresh) {
                    $copy = true;
                }
            } else {
                $copy = true;
            }
            if ($copy) {
                copy($srcPath, $dstPath);
            }
            self::$published[$srcPath] = true;
        }
    }

    private function buildUrl(string $file): string
    {
        $url = $file;
        if ('@' === substr($file, 0, 1)) {
            $trimed = $this->removeAlias($file);
            $url = $this->assetsUrl . '/' . $trimed;
        }
        return $url;
    }

    private function removeAlias(string $file): string
    {
        $parts = explode('/', $file);
        array_shift($parts);
        return implode('/', $parts);
    }
}
