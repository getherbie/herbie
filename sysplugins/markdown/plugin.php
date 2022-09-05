<?php

declare(strict_types=1);

use herbie\Config;
use herbie\FilterInterface;
use herbie\Plugin;
use Psr\Log\LoggerInterface;

class MarkdownSysPlugin extends Plugin
{
    const MODE_NONE = 0;
    const MODE_PARSEDOWN = 1;
    const MODE_PARSEDOWN_EXTRA = 2;

    private Config $config;
    private int $mode;
    private LoggerInterface $logger;

    /**
     * MarkdownPlugin constructor.
     */
    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config->getAsConfig('plugins.markdown');
        $this->logger = $logger;
        if (class_exists('ParsedownExtra')) {
            $this->mode = self::MODE_PARSEDOWN_EXTRA;
        } elseif (class_exists('Parsedown')) {
            $this->mode = self::MODE_PARSEDOWN;
        } else {
            $this->mode = self::MODE_NONE;
            $logger->error('Please install either "erusev/parsedown" or "erusev/parsedown-extra" via composer');
        }
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
            ['markdown', [$this, 'parseMarkdown'], ['is_safe' => ['html']]],
        ];
    }
    
    public function twigFunctions(): array
    {
        if (empty($this->config->get('enableTwigFunction'))) {
            return [];
        }
        return [
            ['markdown', [$this, 'parseMarkdown'], ['is_safe' => ['html']]],
        ];
    }
    
    public function renderSegment(string $context, array $params, FilterInterface $filter): string
    {
        if ($params['page']->format === 'markdown') {
            $context = $this->parseMarkdown($context);
        }
        return $filter->next($context, $params, $filter);
    }

    /**
     * @param string $string
     * @return string
     */
    public function parseMarkdown(string $string): string
    {
        $parser = $this->createParser();
        if ($parser === null) {
            return $string;
        }
        $parser->setUrlsLinked(false);
        return $parser->text($string);
    }

    /**
     * @return Parsedown|ParsedownExtra|null
     */
    private function createParser()
    {
        if ($this->mode === self::MODE_PARSEDOWN_EXTRA) {
            return new ParsedownExtra();
        }
        if ($this->mode === self::MODE_PARSEDOWN) {
            return new Parsedown();
        }
        return null;
    }
}
