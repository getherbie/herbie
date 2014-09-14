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

use Symfony\Component\Yaml\Yaml;
use SplFileInfo;

class FrontMatterLoader
{

    /**
     * @param string $path
     * @return array
     */
    public function load($path)
    {
        $yaml = '';

        $fileInfo = new SplFileInfo($path);
        $fileObject = $fileInfo->openFile('r');

        $i = 0;
        while (!$fileObject->eof()) {
            $line = $fileObject->fgets();
            // head
            if (preg_match('/^---$/', $line)) {
                $i++;
                continue;
            }
            if ($i == 1) {
                $yaml .= $line;
            }
            if ($i > 1) {
                break;
            }
        }

        // Close file handler?
        unset($fileObject);

        return (array) Yaml::parse($yaml);
    }
}
