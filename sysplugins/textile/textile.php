<?php

namespace herbie\sysplugins\textile;

use herbie\Configuration;
use herbie\FilterInterface;
use herbie\Plugin;
use Netcarver\Textile\Parser;
use Psr\Log\LoggerInterface;

class TextilePlugin extends Plugin
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * TextilePlugin constructor.
     * @param Configuration $config
     * @param LoggerInterface $logger
     */
    public function __construct(Configuration $config, LoggerInterface $logger)
    {
        $this->config = $config->plugins->textile;
        if (!class_exists('Netcarver\Textile\Parser')) {
            $logger->error('Please install "netcarver/textile" via composer');
        }
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
        if (!class_exists('Netcarver\Textile\Parser')) {
            return $value;
        }
        try {
            $parser = new Parser();
            return $parser->parse($value);
        } catch (\Throwable $t) {
            return $value;
        }
    }

}
