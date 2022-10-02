<?php

use Twig\TwigFilter;

return new TwigFilter('reverse', function ($string) {
    return strrev($string);
});
