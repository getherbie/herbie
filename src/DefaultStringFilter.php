<?php

declare(strict_types=1);

namespace Herbie;

class DefaultStringFilter
{
    public function __invoke(string $content): string
    {
        return $content;
    }
}
