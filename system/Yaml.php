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

use Symfony\Component\Yaml\Yaml as sfYaml;

/**
 * Yaml offers convenience methods to load and dump YAML.
 *
 * @package Herbie
 */
class Yaml
{

    /**
     * @param string $input
     * @return array
     */
    public static function parse($input)
    {
        return sfYaml::parse($input);
    }

    /**
     * @param string $file
     * @return array
     */
    public static function parseFile($file)
    {
        $input = file_get_contents($file);
        return self::parse($input);
    }

    /**
     * @param array $array
     * @return string
     */
    public static function dump(array $array)
    {
        return sfYaml::dump($array, 100);
    }

}
