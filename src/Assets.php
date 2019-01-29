<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

/**
 * @see: http://fuelphp.com/docs/classes/asset/usage.html
 * @see: http://docs.phalconphp.com/en/latest/reference/assets.html
 */
class Assets
{
    const TYPE_CSS = 0;
    const TYPE_JS = 1;

    /**
     * @var Alias
     */
    private $alias;

    /**
     * @var array
     */
    private $assets = [];

    /**
     * @var string
     */
    private $assetsDir = '/assets';

    /**
     * @var string
     */
    private $assetsUrl;

    /**
     * @var string
     */
    private $assetsPath;

    /**
     * @var int
     */
    private $refresh = 86400;

    /**
     * @var int
     */
    private $chmode = 0755;

    /**
     * @var int
     */
    private static $counter = 0;

    /**
     * @var bool
     */
    private static $sorted = false;

    /**
     * @var array
     */
    private static $published = [];

    /**
     * @param Alias $alias
     * @param Environment $environment
     */
    public function __construct(Alias $alias, Environment $environment)
    {
        $this->alias = $alias;
        $this->assetsPath = $alias->get('@web') . $this->assetsDir;
        $this->assetsUrl = $environment->getBasePath() . $this->assetsDir;
    }

    /**
     * @param array|string $paths
     * @param array $attr
     * @param string $group
     * @param bool $raw
     * @param int $pos
     */
    public function addCss($paths, array $attr = [], string $group = null, bool $raw = false, int $pos = 1): void
    {
        $paths = is_array($paths) ? $paths : [$paths];
        foreach ($paths as $path) {
            $this->addAsset(self::TYPE_CSS, $path, $attr, $group, $raw, $pos);
        }
    }

    /**
     * @param array|string $paths
     * @param array $attr
     * @param string $group
     * @param bool $raw
     * @param int $pos
     */
    public function addJs($paths, array $attr = [], string $group = null, bool $raw = false, int $pos = 1): void
    {
        $paths = is_array($paths) ? $paths : [$paths];
        foreach ($paths as $path) {
            $this->addAsset(self::TYPE_JS, $path, $attr, $group, $raw, $pos);
        }
    }

    /**
     * @param string $group
     * @return string
     */
    public function outputCss(string $group = null): string
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

    /**
     * @param string $group
     * @return string
     */
    public function outputJs(string $group = null): string
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

    /**
     * @param int $type
     * @param string $path
     * @param array $attr
     * @param string $group
     * @param bool $raw
     * @param int $pos
     */
    private function addAsset(
        int $type,
        string $path,
        array $attr,
        string $group = null,
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

    /**
     * return void
     */
    private function sort(): void
    {
        if (!self::$sorted) {
            uasort($this->assets, function ($a, $b) {
                if ($a['pos'] == $b['pos']) {
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

    /**
     * @param int $type
     * @param string $group
     * @return array
     */
    private function collect(int $type, string $group = null): array
    {
        $assets = [];
        foreach ($this->assets as $asset) {
            if (($asset['type'] == $type) && ($asset['group'] == $group)) {
                $assets[] = $asset;
            }
        }
        return $assets;
    }

    /**
     * @param string $path
     * @return bool|int
     */
    private function search(string $path)
    {
        foreach ($this->assets as $index => $asset) {
            if ($asset['path'] == $path) {
                return $index;
            }
        }
        return false;
    }

    /**
     * @return void
     */
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
                mkdir($dstDir, $this->chmode, true);
            }
            $copy = false;
            if (is_file($dstPath)) {
                $delta = time() - filemtime($dstPath);
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

    /**
     * @param string $file
     * @return string
     */
    private function buildUrl(string $file): string
    {
        $url = $file;
        if ('@' == substr($file, 0, 1)) {
            $trimed = $this->removeAlias($file);
            $url = $this->assetsUrl . '/' . $trimed;
        }
        return $url;
    }

    /**
     * @param string $file
     * @return string
     */
    private function removeAlias(string $file): string
    {
        $parts = explode('/', $file);
        array_shift($parts);
        return implode('/', $parts);
    }
}
