<?php

namespace Example;

class TestFilter
{
    public function __invoke(string $content, array $args, $chain)
    {
        return $chain->next($content, $args, $chain);
    }
}
