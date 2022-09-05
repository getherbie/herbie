<?php

declare(strict_types=1);

use herbie\Config;
use herbie\FilterInterface;
use herbie\Plugin;
use Netcarver\Textile\Parser;
use Psr\Log\LoggerInterface;

class TextileSysPlugin extends Plugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * TextilePlugin constructor.
     * @param Config $config
     * @param LoggerInterface $logger
     * @throws \InvalidArgumentException
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
     * @return array
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
     * @return array
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

    /**
     * @param string $context
     * @param array $params
     * @param FilterInterface $filter
     * @return mixed
     */
    public function renderSegment(string $context, array $params, FilterInterface $filter)
    {
        if ($params['page']->format === 'textile') {
            $context = $this->parseTextile($context);
        }
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
