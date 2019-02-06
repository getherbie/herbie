<?php

declare(strict_types=1);

namespace Herbie;

use Zend\EventManager\FilterChain;

class FilterChainManager
{
    private $filters = [];

    /**
     * @param string $filterName
     * @param callable $listener
     * @param int $priority
     */
    public function attach(string $filterName, callable $listener, int $priority = 1)
    {
        $this->getFilters($filterName)->attach($listener, $priority);
    }

    /**
     * @param string $filterName
     * @param $subject
     * @param array $context
     * @return mixed
     */
    public function execute(string $filterName, $subject, array $context)
    {
        $filterChain = $this->getFilters($filterName);
        $filterChain->attach($this->getDefaultFilter($filterName));
        return $filterChain->run($subject, $context);
    }

    /**
     * @param string $filterName
     * @return FilterChain
     */
    private function getFilters(string $filterName)
    {
        if (!isset($this->filters[$filterName])) {
            $this->filters[$filterName] = new FilterChain();
        }
        return $this->filters[$filterName];
    }

    /**
     * @param string $filterName
     * @return \Closure
     */
    private function getDefaultFilter(string $filterName)
    {
        switch ($filterName) {
            default:
                return function ($content) {
                    return $content;
                };
        }
    }
}
