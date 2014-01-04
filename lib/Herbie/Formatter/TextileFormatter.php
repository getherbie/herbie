<?php

namespace Herbie\Formatter;

class TextileFormatter implements FormatterInterface
{
    protected $parser;

    public function __construct()
    {
        $this->parser = new \Netcarver\Textile\Parser();
    }

    public function transform($value)
    {
        $parser = new \Netcarver\Textile\Parser();
        return $parser->textileThis($value);
    }

}