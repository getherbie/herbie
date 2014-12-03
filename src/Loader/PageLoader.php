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
     * @return array
     * @throws \Exception
     */
    public function load($alias, $twigify = true)
    {
        $yaml = '';
        $segments = [];
        $segmentId = 0;

        $content = $this->loadRawContent($alias, $twigify);

        $i = 0;
        foreach(explode("\n", $content) as $line) {
            $line .= "\n";
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
            throw new \Exception("Invalid Front-Matter Block in file {$alias}.");
        }

        $data = (array) Yaml::parse($yaml);
        $data['format'] = pathinfo($alias, PATHINFO_EXTENSION);
        $data['date'] = $this->extractDateFromPath($alias);
        $data['path'] = $alias;

        return [
            'data' => $data,
            'segments' => $segments
        ];
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
    public function loadRawContent($alias, $twigify = true)
    {
        if(!$twigify || is_null($this->twig)) {
            $path = $this->alias->get($alias);
            return file_get_contents($path);
        } else {
            return $this->twig->render($alias);
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
