<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
