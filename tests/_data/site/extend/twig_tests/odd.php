<?php

declare(strict_types=1);

namespace herbie\tests\_data\site\extend\twig_tests;

use Twig\TwigTest;

return new TwigTest('local_odd', fn($value): bool => ($value % 2) !== 0);
