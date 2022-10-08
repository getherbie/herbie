<?php

namespace example\site\extend\twig\filters;

use Twig\TwigFilter;

return new TwigFilter('local_reverse', function ($string) {
    return strrev($string);
});
