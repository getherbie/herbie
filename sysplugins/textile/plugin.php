<?php

declare(strict_types=1);

namespace herbie\sysplugin;

use herbie\Config;
use herbie\FilterInterface;
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
     * @return array[]
     */
    public function twigFilters(): array
    {
        if (empty($this->config->get('enableTwigFilter'))) {
            return [];
        }
        return [
            ['textile', [$this, 'parseTextile'], ['is_safe' => ['html']]],
        ];
    }

    /**
     * @return array[]
     */
    public function twigFunctions(): array
    {
        if (empty($this->config->get('enableTwigFunction'))) {
            return [];
        }
        return [
            ['textile', [$this, 'parseTextile'], ['is_safe' => ['html']]],
        ];
    }

    public function renderSegment(string $context, array $params, FilterInterface $filter): string
    {
        if ($params['page']->format === 'textile') {
            $context = $this->parseTextile($context);
        }
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
