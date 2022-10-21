<?php

declare(strict_types=1);

namespace tests\_data\src;

use herbie\FilterInterface;

class TestFilter
{
    public function __invoke(string $content, array $args, FilterInterface $chain)
    {
        return $chain->next($content, $args, $chain);
    }
}
