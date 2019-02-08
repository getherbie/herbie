<?php

declare(strict_types=1);

namespace Herbie;

class FilterChain
{
    /**
     * @var FilterIterator
     */
    private $filters;

    /**
     * FilterChain constructor.
     */
    public function __construct()
    {
        $this->filters = new FilterIterator();
    }

    /**
     * @param callable $callback
     * @return callable
     */
    public function attach(callable $callback): callable
    {
        $this->filters->insert($callback);
        return $callback;
    }

    /**
     * @param callable $callback
     */
    public function detach(callable $callback): void
    {
        $this->filters->remove($callback);
    }

    /**
     * @param mixed $context
     * @param array $argv
     */
    public function run($context, array $argv = [])
    {
        $filters = $this->getFilters();

        if (count($filters) === 0) {
            return;
        }

        $next = $filters->current();

        return $next($context, $argv, $filters);
    }

    /**
     * @return FilterIterator
     */
    public function getFilters(): FilterIterator
    {
        return $this->filters;
    }

    public function rewind()
    {
        $this->filters->rewind();
    }
}
