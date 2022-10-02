<?php

declare(strict_types=1);

namespace herbie;

final class RenderSegmentFilter
{
    public function __invoke(string $content, array $params, FilterIterator $chain): ?string
    {
        return $chain->next($content, $params, $chain);
    }
}
