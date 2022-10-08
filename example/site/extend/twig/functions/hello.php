<?php

namespace example\site\extend\twig\functions;

use Twig\TwigFunction;

return new TwigFunction('local_hello', function ($name) {
    return "Hello {$name}!";
});
