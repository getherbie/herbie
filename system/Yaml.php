<?php

declare(strict_types=1);

namespace herbie;

use Symfony\Component\Yaml\Yaml as sfYaml;

/**
 * Yaml offers convenience methods to load and dump YAML.
 */
final class Yaml
{
    public static function parseFile(string $file): array
    {
        $input = file_read($file);
        return self::parse($input);
    }

    public static function parse(string $input): array
    {
        $parsed = sfYaml::parse($input);
        return (array)$parsed;
    }

    public static function dump(array $array): string
    {
        return sfYaml::dump($array, 100);
    }
}
