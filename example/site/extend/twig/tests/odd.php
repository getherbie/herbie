<?php

namespace example\site\extend\twig\tests;

use Twig\TwigTest;

return new TwigTest('local_odd', function ($value) {
    return ($value % 2) != 0;
});
