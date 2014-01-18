<?php

namespace Herbie\Loader;

use SplFileInfo;
use Symfony\Component\Yaml\Parser;

class FrontMatterLoader
{

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * @param string $path
     * @return array
     */
    public function load($path)
    {
        $data = '';

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
                $data .= $line;
            }
            if ($i > 1) {
                break;
            }
        }

        // Close file handler?
        unset($fileObject);

        return (array)$this->parser->parse($data);
   }

}
