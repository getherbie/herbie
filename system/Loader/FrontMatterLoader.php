<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Loader;

use Herbie\Yaml;

class FrontMatterLoader
{

    /**
     * @param string $path
     * @return array
     */
    public function load($path)
    {
        if(!defined('UTF8_BOM')) {
            define('UTF8_BOM', chr(0xEF).chr(0xBB).chr(0xBF));
        }
        
        $yaml = '';

        $fileObject = new \SplFileObject($path);

        $i = 0;
        foreach ($fileObject as $line) {
            // strip BOM from the beginning and \n and \r from end of line
            $line = rtrim(ltrim($line, UTF8_BOM), "\n\r");
            if (preg_match('/^---$/', $line)) {
                $i++;
                continue;
            }
            if ($i > 1) {
                break;
            }
            if ($i == 1) {
                // add PHP_EOL to end of line
                $yaml .= $line . PHP_EOL;
            }
        }

        unset($fileObject);

        return (array) Yaml::parse($yaml);
    }
}
