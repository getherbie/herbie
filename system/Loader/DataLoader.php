<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Loader;

use Herbie\Yaml;

/**
 * Loads site data.
 */
class DataLoader
{

    /**
     * @var array
     */
    protected $extensions;

    /**
     * @param array $extensions
     */
    public function __construct(array $extensions = [])
    {
        $this->extensions = $extensions;
    }

    /**
     * @param string $path
     * @return array
     */
    public function load($path)
    {
        $data = [];

        // dir does not exist or is not readable
        if (!is_readable($path)) {
            return $data;
        }

        $files = scandir($path);
        foreach ($files as $file) {
            if (substr($file, 0, 1) === '.') {
                continue;
            }
            $info = pathinfo($file);
            if (!in_array($info['extension'], $this->extensions)) {
                continue;
            }
            $key = $info['filename'];
            $yaml = file_get_contents($path . '/' . $file);
            $data[$key] = Yaml::parse($yaml);
        }

        return $data;
    }
}
