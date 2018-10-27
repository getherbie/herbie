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

use Herbie\Alias;
use Herbie\Exception\ResourceNotFoundException;
use Herbie\Helper\PathHelper;
use Herbie\Yaml;

/**
 * Loads the whole page.
 */
class PageLoader
{
    protected $alias;
    protected $page;

    /**
     * @param Alias $alias
     */
    public function __construct(Alias $alias)
    {
        $this->alias = $alias;
    }

    /**
     * @param string $alias
     * @param bool $addDefFields
     * @return array
     * @throws \Exception
     */
    public function load($alias, $addDefFields = true)
    {
        $content = $this->readFile($alias);
        list($yaml, $segments) = $this->parseContent($content);

        $data = (array) Yaml::parse($yaml);
        if ($addDefFields) {
            $data['format'] = isset($data['format']) ? $data['format'] : pathinfo($alias, PATHINFO_EXTENSION);
            $data['date'] = isset($data['date']) ? $data['date'] : PathHelper::extractDateFromPath($alias);
            $data['path'] = isset($data['path']) ? $data['path'] : $alias;
        }
        return [
            'data' => $data,
            'segments' => $segments
        ];
    }

    /**
     * @param string $alias
     * @return array
     */
    public function loadRaw($alias)
    {
        $content = $this->readFile($alias);
        return $this->parseContent($content);
    }

    public function save($alias, array $data = [], array $segments = [])
    {
        // page data
        $content = '---' . PHP_EOL;
        $content .= Yaml::dump($data);
        $content .= '---' . PHP_EOL;

        // page segments
        if (array_key_exists(0, $segments)) {
            $content .= $segments[0];
            $content .= PHP_EOL;
            unset($segments[0]);
        }
        if (array_key_exists('', $segments)) {
            $content .= $segments[''];
            $content .= PHP_EOL;
            unset($segments['']);
        }

        foreach ($segments as $key => $value) {
            $content .= '--- ' . $key . ' ---' . PHP_EOL;
            $content .= $value;
            $content .= PHP_EOL;
        }
        $path = $this->alias->get($alias);
        return file_put_contents($path, $content);
    }

    /**
     * @param string $content
     * @return array
     * @throws \Exception
     */
    protected function parseContent($content)
    {
        if(!defined('UTF8_BOM')) {
            define('UTF8_BOM', chr(0xEF).chr(0xBB).chr(0xBF));
        }
        
        $yaml = '';
        $segments = [];

        $matched = preg_match('/^['.UTF8_BOM.']*-{3}\r?\n(.*)\r?\n-{3}\R(.*)/ms', $content, $matches);

        if ($matched === 1 && count($matches) == 3) {
            $yaml = $matches[1];

            $splitted = preg_split('/^-{3} (.+) -{3}\R?$/m', $matches[2], -1, PREG_SPLIT_DELIM_CAPTURE);

            $count = count($splitted);
            if ($count %2 == 0) {
                throw new \Exception('Fehler beim Auslesen der Seite.');
            }

            $segments[] = array_shift($splitted);
            $ct_splitted = count($splitted);
            for ($i=0; $i<$ct_splitted; $i=$i+2) {
                $key = $splitted[$i];
                $value = $splitted[$i+1];
                if (array_key_exists($key, $segments)) {
                    $segments[$key] .= $value;
                } else {
                    $segments[$key] = $value;
                }
            }

            $i = 0;
            $last = count($segments) - 1;
            foreach ($segments as $key => $segment) {
                $segments[$key] = ($i == $last) ? $segment : preg_replace('/\R?$/', '', $segment, 1);
                $i++;
            }
        }

        return [$yaml, $segments];
    }

    /**
     * @param string $alias
     * @return string
     * @throws ResourceNotFoundException
     */
    protected function readFile($alias)
    {
        $path = $this->alias->get($alias);
        // suppress E_WARNING since we throw an exception on error
        $contents = @file_get_contents($path);
        if (false === $contents) {
            throw new ResourceNotFoundException('Page "' . $alias . '" does not exist.');
        }
        return $contents;
    }
}
