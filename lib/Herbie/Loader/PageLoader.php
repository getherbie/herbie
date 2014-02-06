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
use Herbie\Page;


/**
 * Loads the whole page.
 */
class PageLoader
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @param Parser $parser
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param string $path
     * @return array
     * @throws Exception
     */
    public function load($path)
    {
        $fileInfo = new SplFileInfo($path);
        $fileObj = $fileInfo->openFile('r');

        $yaml = '';
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
                $yaml .= $line;
            }
            // segments
            if ($i > 1) {
                // segments
                if (preg_match('/^--- ([A-Za-z0-9_]+) ---$/', $line, $matches)) {
                    $segmentId = $matches[1];
                    continue;
                }
                if(!array_key_exists($segmentId, $segments)) {
                    $segments[$segmentId] = '';
                }
                $segments[$segmentId] .= $line;
            }
        }

        if($i<2) {
            throw new Exception("Invalid Front-Matter Block in file {$path}.");
        }

        unset($fileObj);

        $data = array_merge(
            ['type' => $fileInfo->getExtension()],
            (array)$this->parser->parse($yaml)
        );

        $page = new Page();
        $page->setData($data);
        $page->setSegments($segments);
        
        return $page;
    }

}