<?php

declare(strict_types=1);

namespace herbie;

interface FilterInterface
{
    /**
     * @param array|string $context
     * @return array|string
     */
    public function next($context = null, array $params = [], ?FilterInterface $chain = null);
}
