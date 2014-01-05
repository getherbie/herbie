<?php

namespace Herbie\Formatter;

class MarkdownFormatter implements FormatterInterface
{

    /**
     * @param string $value
     * @return string
     */
    public function transform($value)
    {
        return \Michelf\Markdown::defaultTransform($value);
    }

}