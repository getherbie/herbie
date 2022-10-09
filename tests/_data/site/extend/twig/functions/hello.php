<?php

namespace tests\_data\site\extend\twig\functions;

use Twig\TwigFunction;

return new TwigFunction('local_hello', function ($name) {
    return "Hello {$name}!";
});
