<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

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
        $filterChain->attach($this->getDefaultFilter($filterName));
        $filterChain->rewind();
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
