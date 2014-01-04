<?php

namespace Herbie\Formatter;

interface FormatterInterface
{

    /**
     * @param string $value
     * @return string
     */
    public function transform($value);
    
}