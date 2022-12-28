<?php

declare(strict_types=1);

namespace herbie\tests\_data\site\extend\twig_functions;

use Twig\TwigFunction;

return new TwigFunction('local_hello', function ($name) {
    return "Hello {$name}!";
});
