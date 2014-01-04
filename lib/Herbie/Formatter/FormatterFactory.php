<?php

namespace Herbie\Formatter;


class FormatterFactory
{

    public static function create($type)
    {
        if (in_array($type, ['md', 'markdown'])) {
            $formatter = new MarkdownFormatter();
        } elseif (in_array($type, ['textile'])) {
            $formatter = new TextileFormatter();
        } else {
            $formatter = new RawFormatter();
        }
        return $formatter;
    }

}