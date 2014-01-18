<?php

namespace Herbie\Formatter;

use Michelf\MarkdownExtra;

class MarkdownFormatter implements FormatterInterface
{

    /**
     * @param string $value
     * @return string
     */
    public function transform($value)
    {
        $parser = new MarkdownExtra();
        return $parser->transform($value);
    }

}