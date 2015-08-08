<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Helper;

use Herbie\Yaml;

class PageHelper
{

    public static function updateData($filepath, array $data)
    {
        $content = file_get_contents($filepath);
        $matches = self::pregMatch($content);
        if (count($matches) == 3) {
            $newContent = '';
            $newContent .= '---' . PHP_EOL;
            $newContent .= Yaml::dump($data);
            $newContent .= '---' . PHP_EOL;
            $newContent .= $matches[2];

            return file_put_contents($filepath, $newContent);
        }
        return false;
    }

    public static function updateSegments($filepath, array $segments)
    {
        $content = file_get_contents($filepath);
        $matches = self::pregMatch($content);
        if (count($matches) == 3) {
            $newContent = '';
            $newContent .= '---' . PHP_EOL;
            $newContent .= $matches[1];
            $newContent .= PHP_EOL;
            $newContent .= '---';

            if (array_key_exists(0, $segments)) {
                $newContent .= PHP_EOL;
                $newContent .= $segments[0];
                #$newContent .= PHP_EOL;
                unset($segments[0]);
            }
            if (array_key_exists('', $segments)) {
                $newContent .= PHP_EOL;
                $newContent .= $segments[''];
                #$newContent .= PHP_EOL;
                unset($segments['']);
            }
            foreach ($segments as $key => $value) {
                $newContent .= PHP_EOL . '--- ' . $key . ' ---' . PHP_EOL;
                $newContent .= $value;
                #$newContent .= PHP_EOL;
            }

            return file_put_contents($filepath, $newContent);
        }
        return false;
    }

    protected static function pregMatch($content)
    {
        // @see https://github.com/kzykhys/YamlFrontMatter/blob/master/src/KzykHys/FrontMatter/FrontMatter.php
        $matched = preg_match('/^-{3}\r?\n(.*)\r?\n-{3}\r?\n(.*)/s', $content, $matches);
        return $matches;
    }
}
