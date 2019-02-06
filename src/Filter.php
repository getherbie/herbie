<?php

declare(strict_types=1);

namespace Herbie;

interface Filter
{
    /**
     * @param mixed|null $context
     * @param array $params
     * @param Filter|null $chain
     * @return mixed|null
     */
    public function next($context = null, array $params = [], Filter $chain = null);
}
