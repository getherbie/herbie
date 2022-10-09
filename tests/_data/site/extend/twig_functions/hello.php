<?php

namespace tests\_data\site\extend\twig_functions;

use Twig\TwigFunction;

return new TwigFunction('local_hello', function ($name) {
    return "Hello {$name}!";
});
