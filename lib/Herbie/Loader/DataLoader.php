<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Loader;

use Symfony\Component\Yaml\Parser;

/**
 * Loads site data.
 */
class DataLoader
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var array
     */
    protected $extensions;

    /**
     * @param Parser $parser
     * @param array $extensions
     */
    public function __construct(Parser $parser, array $extensions = [])
    {
        $this->parser = $parser;
        $this->extensions = $extensions;
    }

    /**
     * @param string $path
     * @return array
     */
    public function load($path)
    {
        $data = [];
        $files = scandir($path);
        foreach ($files as $file) {
            if (substr($file, 0, 1) === '.') {
                continue;
            }
            $info = pathinfo($file);
            if (!in_array($info['extension'], $this->extensions)) {
                continue;
            }
            $key = $info['filename'];
            $yaml = file_get_contents($path . '/' . $file);
            $data[$key] = $this->parser->parse($yaml);
        }

        return $data;
    }
}
