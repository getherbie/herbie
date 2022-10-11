<?php

declare(strict_types=1);

namespace herbie;

final class FilterChainManager
{
    /** @var FilterChain[] */
    private array $filters = [];

    public function attach(string $filterName, callable $listener): void
    {
        $this->getFilters($filterName)->attach($listener);
    }

    /**
     * @param mixed $subject
     * @return mixed
     */
    public function execute(string $filterName, $subject, array $context)
    {
        $filterChain = $this->getFilters($filterName);
        $filterChain->attach($this->getDefaultFilter($filterName));
        $filterChain->rewind();
        return $filterChain->run($subject, $context);
    }

    public function getAllFilters(): array
    {
        $items = [];
        foreach ($this->filters as $category => $filterChain) {
            $filters = $filterChain->getFilters()->items();
            foreach ($filters as $filter) {
                $items[] = [
                    $category,
                    get_callable_name($filter)
                ];
            }
        }
        return $items;
    }
    
    private function getFilters(string $filterName): FilterChain
    {
        if (!isset($this->filters[$filterName])) {
            $this->filters[$filterName] = new FilterChain();
        }
        return $this->filters[$filterName];
    }

    private function getDefaultFilter(string $filterName): \Closure
    {
        switch ($filterName) {
            default:
                return function ($content) {
                    return $content;
                };
        }
    }
}
