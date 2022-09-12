<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

final class FilterChainManager
{
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
