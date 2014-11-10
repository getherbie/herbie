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

use Herbie\Page;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads the whole page.
 */
class PageLoader
{

    /**
     * @param string $path
     * @return array
     * @throws \Exception
     */
    public function load($path)
    {
        $yaml = '';
        $segments = [];
        $segmentId = 0;

        $fileObject = new \SplFileObject($path);

        $i = 0;
        foreach($fileObject as $line) {
            if (preg_match('/^---$/', $line)) {
                $i++;
                continue;
            }
            if ($i == 1) {
                $yaml .= $line;
            }
            if ($i > 1) {
                if (preg_match('/^--- ([A-Za-z0-9_]+) ---$/', $line, $matches)) {
                    $segmentId = $matches[1];
                    continue;
                }
                if (!array_key_exists($segmentId, $segments)) {
                    $segments[$segmentId] = '';
                }
                $segments[$segmentId] .= $line;
            }
        }

        if ($i < 2) {
            throw new \Exception("Invalid Front-Matter Block in file {$path}.");
        }

        $date = $this->extractDateFrom($fileObject->getFilename());
        $data = (array) Yaml::parse($yaml);

        $page = new Page();
        $page->setFormat($fileObject->getExtension());
        $page->setDate($date);
        $page->setData($data);
        $page->setSegments($segments);
        $page->setPath($path);

        unset($fileObject);

        return $page;
    }

    /**
     * @param string $filename
     * @return string
     */
    protected function extractDateFrom($filename)
    {
        if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}).*$/', $filename, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
