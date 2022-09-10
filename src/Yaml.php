<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

use Symfony\Component\Yaml\Yaml as sfYaml;

/**
 * Yaml offers convenience methods to load and dump YAML.
 *
 * @package Herbie
 */
final class Yaml
{
    public static function parse(string $input): array
    {
        $parsed = sfYaml::parse($input);
        return (array)$parsed;
    }

    public static function parseFile(string $file): array
    {
        $input = file_get_contents($file);
        return self::parse($input);
    }

    public static function dump(array $array): string
    {
        return sfYaml::dump($array, 100);
    }
}
