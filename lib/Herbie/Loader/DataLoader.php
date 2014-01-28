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
     * @var string
     */
    protected $path;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var array
     */
    protected $extensions;

    /**
     * @param string $path
     * @param Parser $parser
     * @param array $extensions
     */
    public function __construct($path, Parser $parser, array $extensions = [])
    {
        $this->path = $path;
        $this->parser = $parser;
        $this->extensions = $extensions;
    }

    /**
     * @return array
     */
    public function load()
    {
        $data = [];
        $files = scandir($this->path);
        foreach($files AS $file) {
            if(substr($file, 0, 1) === '.') {
                continue;
            }
            $info = pathinfo($file);
            if(!in_array($info['extension'], $this->extensions)) {
                continue;
            }
            $key = $info['filename'];
            $content = file_get_contents($this->path.'/'.$file);
            $data[$key] = $this->parser->parse($content);
        }

        return $data;
    }

}