<?php

declare(strict_types=1);

namespace herbie\sysplugins\markdown;

use herbie\Configuration;
use herbie\FilterInterface;
use herbie\Plugin;
use ParsedownExtra;

class MarkdownPlugin extends Plugin
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * MarkdownPlugin constructor.
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config->plugins->markdown;
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
            ['markdown', [$this, 'parseMarkdown'], ['is_safe' => ['html']]],
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
            ['markdown', [$this, 'parseMarkdown'], ['is_safe' => ['html']]],
        ];
    }

    /**
     * @param string $context
     * @param array $params
     * @param FilterInterface $filter
     * @return mixed|null
     * @throws \Exception
     */
    public function renderSegment(string $context, array $params, FilterInterface $filter)
    {
        $context = $this->parseMarkdown($context);
        return $filter->next($context, $params, $filter);
    }

    /**
     * @param string $string
     * @return string
     * @throws \Exception
     */
    public function parseMarkdown(string $string): string
    {
        $parser = new ParsedownExtra();
        $parser->setUrlsLinked(false);
        $html = $parser->text($string);
        return $html;
    }
}
