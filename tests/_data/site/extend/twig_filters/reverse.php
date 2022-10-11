<?php

namespace tests\_data\site\extend\twig_filters;

use Twig\TwigFilter;

return new TwigFilter('local_reverse', function ($string) {
    return strrev($string);
});
