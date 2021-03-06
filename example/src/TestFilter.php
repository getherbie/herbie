<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace example;

class TestFilter
{
    public function __invoke(string $content, array $args, $chain)
    {
        return $chain->next($content, $args, $chain);
    }
}
