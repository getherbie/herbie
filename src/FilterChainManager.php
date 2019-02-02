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
     */
    public function attach(string $filterName, callable $listener)
    {
        $this->getFilters($filterName)->attach($listener);
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
}
