<?php

use Twig\TwigFilter;

return new TwigFilter('local_reverse', function ($string) {
    return strrev($string);
});
