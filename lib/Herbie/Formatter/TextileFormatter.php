<?php

namespace Herbie\Formatter;

class TextileFormatter implements FormatterInterface
{

    /**
     * @param string $value
     * @return string
     */
    public function transform($value)
    {
        $parser = new \Netcarver\Textile\Parser();
        return $parser->textileThis($value);
    }

}