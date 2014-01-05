<?php

namespace Herbie\Formatter;

class RawFormatter implements FormatterInterface
{

    /**
     * @param string $value
     * @return string
     */
    public function transform($value)
    {
        return $value;
    }

}