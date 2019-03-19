<?php

namespace herbie\sysplugins\textile;

use herbie\Configuration;
use herbie\FilterInterface;
use herbie\Plugin;

class TextilePlugin extends Plugin
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * TextilePlugin constructor.
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config->plugins->textile;
    }

    /**
     * @return array
     */
    public function filters(): array
    {
        return [
            ['renderSegment', [$this, 'renderSegment']]
        ];
    }

    /**
     * @return array
     */
    public function twigFilters(): array
    {
        if (empty($this->config->twigFilter)) {
            return [];
        }
        return [
            ['textile', [$this, 'parseTextile'], ['is_safe' => ['html']]],
        ];
    }

    /**
     * @return array
     */
    public function twigFunctions(): array
    {
        if (empty($this->config->twigFunction)) {
            return [];
        }
        return [
            ['textile', [$this, 'parseTextile'], ['is_safe' => ['html']]],
        ];
    }

    /**
     * @param string $context
     * @param array $params
     * @param FilterInterface $filter
     * @return mixed
     */
    public function renderSegment(string $context, array $params, FilterInterface $filter)
    {
        $context = $this->parseTextile($context);
        return $filter->next($context, $params, $filter);
    }

    /**
     * @param string $value
     * @return string
     */
    public function parseTextile(string $value): string
    {
        try {
            $parser = new \Netcarver\Textile\Parser();
            return $parser->parse($value);
        } catch (\Throwable $t) {
            return $value;
        }
    }

}
