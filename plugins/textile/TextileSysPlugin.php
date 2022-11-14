<?php

declare(strict_types=1);

namespace herbie\sysplugin\textile;

use herbie\Config;
use herbie\FilterInterface;
use herbie\Page;
use herbie\Plugin;
use Netcarver\Textile\Parser;
use Psr\Log\LoggerInterface;

final class TextileSysPlugin extends Plugin
{
    private Config $config;

    /**
     * TextilePlugin constructor.
     */
    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config->getAsConfig('plugins.textile');
        if (!class_exists('Netcarver\Textile\Parser')) {
            $logger->error('Please install "netcarver/textile" via composer');
        }
    }

    public function interceptingFilters(): array
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
            ['textile', [$this, 'parseTextile'], ['is_safe' => ['html']]],
        ];
    }

    public function twigFunctions(): array
    {
        if (!$this->config->getAsBool('enableTwigFunction')) {
            return [];
        }
        return [
            ['textile', [$this, 'parseTextile'], ['is_safe' => ['html']]],
        ];
    }

    /**
     * @param array{page: Page, routeParams: array<string, mixed>} $params
     */
    public function renderSegment(string $context, array $params, FilterInterface $filter): string
    {
        if ($params['page']->format === 'textile') {
            $context = $this->parseTextile($context);
        }
        /** @var string */
        return $filter->next($context, $params, $filter);
    }

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
