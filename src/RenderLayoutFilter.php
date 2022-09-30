<?php

/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

final class RenderLayoutFilter
{
    public function __invoke(string $content, array $params, FilterIterator $chain): ?string
    {
        return $chain->next($content, $params, $chain);
    }
}
