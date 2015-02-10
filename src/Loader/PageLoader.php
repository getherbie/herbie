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
    protected $alias;
    protected $twig;
    protected $page;

    /**
     * @param \Herbie\Alias $alias
     */
    public function __construct(\Herbie\Alias $alias)
    {
        $this->alias = $alias;
    }

    /**
     * @param string $alias
     * @param bool $twigify
     * @param bool $addDefFields
     * @return array
     * @throws \Exception
     */
    public function load($alias, $twigify = true, $addDefFields = true)
    {
        $content = $this->readFile($alias, $twigify);
        list($yaml, $segments) = $this->parseContent($content);

        $data = (array) Yaml::parse($yaml);
        if ($addDefFields) {
            $data['format'] = pathinfo($alias, PATHINFO_EXTENSION);
            $data['date'] = $this->extractDateFromPath($alias);
            $data['path'] = $alias;
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
        $content = $this->readFile($alias, false);
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
        $yaml = '';
        $segments = [];

        $matched = preg_match('/^-{3}\r?\n(.*)\r?\n-{3}\R(.*)/ms', $content, $matches);

        if ($matched === 1 && count($matches) == 3) {
            $yaml = $matches[1];

            $splitted = preg_split('/^-{3} (.+) -{3}$\n?/m', $matches[2], -1, PREG_SPLIT_DELIM_CAPTURE);

            $count = count($splitted);
            if ($count %2 == 0) {
                throw new \Exception('Fehler beim Auslesen der Seite.');
            }

            $segments[] = array_shift($splitted);
            for ($i=0; $i<count($splitted); $i=$i+2) {
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
     * @param \Twig_Environment $twig
     */
    public function setTwig(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @return void
     */
    public function unsetTwig()
    {
        $this->twig = null;
    }

    /**
     * @param string $alias
     * @param bool $twigify
     * @return string
     */
    protected function readFile($alias, $twigify = true)
    {
        $path = $this->alias->get($alias);
        if (!$twigify || is_null($this->twig)) {
            return file_get_contents($path);
        }
        try {
            return $this->twig->render($alias);
        } catch (\Twig_Error_Runtime $e) {
            return file_get_contents($path);
        } catch (\Twig_Error_Syntax $e) {
            return file_get_contents($path);
        }
    }

    /**
     * @param string $alias
     * @return string
     * @todo Duplicate code in Herbie\Menu\Post\Builder
     */
    protected function extractDateFromPath($alias)
    {
        $filename = basename($alias);
        if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}).*$/', $filename, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
