<?php

declare(strict_types=1);

namespace herbie\sysplugin\rest;

use herbie\Config;
use herbie\FilterInterface;
use herbie\Plugin;
use Psr\Log\LoggerInterface;

final class RestSysPlugin extends Plugin
{
    private Config $config;
    private LoggerInterface $logger;
    private bool $parserClassExists;

    /**
     * RestSysPlugin constructor.
     */
    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config->getAsConfig('plugins.rest');
        $this->logger = $logger;
        $this->parserClassExists = class_exists('\\Doctrine\\RST\\Parser');
    }

    public function filters(): array
    {
        return [
            ['renderSegment', [$this, 'renderSegment']]
        ];
    }

    public function twigFilters(): array
    {
        if (empty($this->config->get('enableTwigFilter'))) {
            return [];
        }
        return [
            ['rest', [$this, 'parseRest'], ['is_safe' => ['html']]],
        ];
    }

    public function twigFunctions(): array
    {
        if (empty($this->config->get('enableTwigFunction'))) {
            return [];
        }
        return [
            ['rest', [$this, 'parseRest'], ['is_safe' => ['html']]],
        ];
    }

    public function renderSegment(string $context, array $params, FilterInterface $filter): string
    {
        if ($params['page']->format === 'rest') {
            $context = $this->parseRest($context);
        }
        return $filter->next($context, $params, $filter);
    }

    public function parseRest(string $string): string
    {
        if (!$this->parserClassExists) {
            return $string;
        }
        $parser = new \Doctrine\RST\Parser();
        $document = $parser->parse($string);
        return $document->render();
    }
}
