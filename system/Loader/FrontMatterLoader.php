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

class FrontMatterLoader
{

    /**
     * @param string $path
     * @return array
     */
    public function load($path)
    {
        $yaml = '';

        $fileObject = new \SplFileObject($path);

        $i = 0;
        foreach ($fileObject as $line) {
            // strip \n and \r from end of line
            $line = rtrim($line, "\n\r");
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
