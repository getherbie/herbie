<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @var \Herbie\Alias
     */
    protected $alias;

    /**
     * @var array
     */
    protected $assets = [];

    /**
     * @var string
     */
    protected $assetsDir = '/assets';

    /**
     * @var string
     */
    protected $assetsUrl;

    /**
     * @var string
     */
    protected $assetsPath;

    /**
     * @var int
     */
    protected $refresh = 86400;

    /**
     * @var octal
     */
    protected $chmode = 0755;

    /**
     * @var int
     */
    protected static $counter = 0;

    /**
     * @var bool
     */
    protected static $sorted = false;

    /**
     * @var array
     */
    protected static $published = [];

    /**
     * @param Alias $alias
     * @param string $baseUrl
     */
    public function __construct(Alias $alias, $baseUrl)
    {
        $this->alias = $alias;
        $this->assetsPath = $alias->get('@web') . $this->assetsDir;
        $this->assetsUrl = rtrim($baseUrl, '/') . $this->assetsDir;
    }

    /**
     * @param array|string $paths
     * @param array $attr
     * @param string $group
     * @param bool $raw
     * @param int $pos
     */
    public function addCss($paths, $attr = [], $group = null, $raw = false, $pos = 1)
    {
        $paths = is_array($paths) ? $paths : [$paths];
        foreach ($paths as $path) {
            $this->addAsset(self::TYPE_CSS, $path, $group, $attr, $raw, $pos);
        }
    }

    /**
     * @param array|string $paths
     * @param array $attr
     * @param string $group
     * @param bool $raw
     * @param int $pos
     */
    public function addJs($paths, $attr = [], $group = null, $raw = false, $pos = 1)
    {
        $paths = is_array($paths) ? $paths : [$paths];
        foreach ($paths as $path) {
            $this->addAsset(self::TYPE_JS, $path, $attr, $group, $raw, $pos);
        }
    }

    /**
     * @param string $group
     */
    public function outputCss($group = null)
    {
        $this->sort();
        $this->publish();
        foreach ($this->collect(self::TYPE_CSS, $group) as $asset) {
            if (empty($asset['raw'])) {
                $href = $this->buildUrl($asset['path']);
                echo sprintf('<link href="%s" type="text/css" rel="stylesheet">', $href);
            } else {
                echo sprintf('<style>%s</style>', $asset['path']);
            }
        }
    }

    /**
     * @param string $group
     */
    public function outputJs($group = null)
    {
        $this->sort();
        $this->publish();
        foreach ($this->collect(self::TYPE_JS, $group) as $asset) {
            if (empty($asset['raw'])) {
                $href = $this->buildUrl($asset['path']);
                echo sprintf('<script src="%s"></script>', $href);
            } else {
                echo sprintf('<script>%s</script>', $asset['path']);
            }
        }
    }

    /**
     * @param int $type
     * @param string $path
     * @param array $attr
     * @param string $group
     * @param bool $raw
     * @param int $pos
     */
    protected function addAsset($type, $path, $attr, $group, $raw, $pos)
    {
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
    protected function sort()
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
    protected function collect($type, $group = null)
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
     * @param $path
     * @return bool|int
     */
    protected function search($path)
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
    protected function publish()
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
    protected function buildUrl($file)
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
    protected function removeAlias($file)
    {
        $parts = explode('/', $file);
        array_shift($parts);
        return implode('/', $parts);
    }
}
