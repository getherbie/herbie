<?php

namespace Herbie\Formatter;

class RawFormatter implements FormatterInterface
{

    public function transform($value)
    {
        return $value;
    }

}