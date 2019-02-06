<?php

namespace Herbie;

interface FilterInterface
{
    /**
     * @param mixed|null $context
     * @param array $params
     * @param FilterInterface|null $chain
     * @return mixed|null
     */
    public function next($context = null, array $params = [], FilterInterface $chain = null);
}
