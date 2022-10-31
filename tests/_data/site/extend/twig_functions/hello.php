<?php

declare(strict_types=1);

namespace tests\_data\site\extend\twig_functions;

use Twig\TwigFunction;

return new TwigFunction('local_hello', function ($name) {
    return "Hello {$name}!";
});
