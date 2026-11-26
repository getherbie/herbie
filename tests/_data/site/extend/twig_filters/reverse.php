<?php

declare(strict_types=1);

namespace herbie\tests\_data\site\extend\twig_filters;

use Twig\TwigFilter;

return new TwigFilter('local_reverse', fn($string): string => strrev($string));
