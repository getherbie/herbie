<?php

declare(strict_types=1);

namespace tests\_data\site\extend\twig_tests;

use Twig\TwigTest;

return new TwigTest('local_odd', function ($value) {
    return ($value % 2) != 0;
});
