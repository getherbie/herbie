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

use Exception;
use SplFileInfo;
use Symfony\Component\Yaml\Parser;


/**
 * Loads the whole page.
 */
class PageLoader
{

    /**
     * @var SplFileInfo
     */
    protected $fileInfo;

    /**
     *
     * @var Parser
     */
    protected $parser;

    /**
     * @param string $path
     * @param Parser $parser
     */
    public function __construct($path, Parser $parser)
    {
        $this->fileInfo = new SplFileInfo($path);
        $this->parser = $parser;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->fileInfo->getExtension();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function load()
    {
        $fileObj = $this->fileInfo->openFile('r');

        $data = '';
        $segments = [];
        $segmentId = 0;

        $i = 0;
        while (!$fileObj->eof()) {
            $line = $fileObj->fgets();
            if (preg_match('/^---$/', $line)) {
                $i++;
                continue;
            }
            // data
            if ($i == 1) {
                $data .= $line;
            }
            // segments
            if ($i > 1) {
                // segments
                if (preg_match('/^--- ([A-Za-z0-9_]+) ---$/', $line, $matches)) {
                    $segmentId = $matches[1];
                    continue;
                }
                if(array_key_exists($segmentId, $segments)) {
                    $segments[$segmentId] .= $line;
                } else {
                    $segments[$segmentId] = '';
                }
            }
        }

        if($i<2) {
            throw new Exception("Invalid Front-Matter Block in file {$path}.");
        }

        unset($fileObj);

        return [
            'data' => (array)$this->parser->parse($data),
            'segments' => $segments
        ];
    }

}