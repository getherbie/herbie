<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

final class FilterChain
{
    private FilterIterator $filters;

    /**
     * FilterChain constructor.
     */
    public function __construct()
    {
        $this->filters = new FilterIterator();
    }

    public function attach(callable $callback): callable
    {
        $this->filters->insert($callback);
        return $callback;
    }

    public function detach(callable $callback): void
    {
        $this->filters->remove($callback);
    }

    /**
     * @param mixed $context
     * @return array|string|null depending on the context
     */
    public function run($context, array $argv = [])
    {
        $filters = $this->getFilters();

        if (count($filters) === 0) {
            return null;
        }

        $next = $filters->current();

        return $next($context, $argv, $filters);
    }

    public function getFilters(): FilterIterator
    {
        return $this->filters;
    }

    public function rewind(): void
    {
        $this->filters->rewind();
    }
}
