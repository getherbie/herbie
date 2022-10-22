<?php

declare(strict_types=1);

namespace herbie\sysplugin\rest;

use herbie\Config;
use herbie\FilterInterface;
use herbie\Page;
use herbie\Plugin;

final class RestSysPlugin extends Plugin
{
    private Config $config;
    private bool $parserClassExists;

    /**
     * RestSysPlugin constructor.
     */
    public function __construct(Config $config)
    {
        $this->config = $config->getAsConfig('plugins.rest');
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
        if (!$this->config->getAsBool('enableTwigFilter')) {
            return [];
        }
        return [
            ['rest', [$this, 'parseRest'], ['is_safe' => ['html']]],
        ];
    }

    public function twigFunctions(): array
    {
        if (!$this->config->getAsBool('enableTwigFunction')) {
            return [];
        }
        return [
            ['rest', [$this, 'parseRest'], ['is_safe' => ['html']]],
        ];
    }

    /**
     * @param array{page: Page, routeParams: array<string, mixed>} $params
     */
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
