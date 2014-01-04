<?php

namespace Herbie\Formatter;

class MarkdownFormatter implements FormatterInterface
{

    public function getContent()
    {

    }

    public function transform($value)
    {
        return \Michelf\Markdown::defaultTransform($value);
    }

}