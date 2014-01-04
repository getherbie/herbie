<?php

namespace Herbie\Loader;

/**
 * Loads site data.
 *
 * @author Thomas Breuss <thomas.breuss@zephir.ch>
 */
class DataLoader
{
    protected $path;
    protected $parser;
    protected $extensions;

    public function __construct($path, $parser, array $extensions = [])
    {
        $this->path = $path;
        $this->parser = $parser;
    }

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
            $data[$key] = $parser->parse($content);
        }

        return $data;
    }

}