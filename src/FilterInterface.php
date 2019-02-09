<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
