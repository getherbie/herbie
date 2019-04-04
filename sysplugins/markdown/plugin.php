<?php

declare(strict_types=1);

use herbie\Config;
use herbie\FilterInterface;
use herbie\Plugin;
use \Parsedown as Parsedown;
use \ParsedownExtra as ParsedownExtra;
use Psr\Log\LoggerInterface;

class MarkdownSysPlugin extends Plugin
{
    const MODE_PARSEDOWN = 1;
    const MODE_PARSEDOWN_EXTRA = 2;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var int
     */
    private $mode;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * MarkdownPlugin constructor.
     * @param Config $config
     * @throws \InvalidArgumentException
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
            $logger->error('Please install either "erusev/parsedown" or "erusev/parsedown-extra" via composer');
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
        if (empty($this->config->get('twigFilter'))) {
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
        if (empty($this->config->get('twigFunction'))) {
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
        $parser = $this->createParser();
        if ($parser) {
            $parser->setUrlsLinked(false);
            $string = $parser->text($string);
        }
        return $string;
    }

    /**
     * @return Parsedown|ParsedownExtra|null
     */
    private function createParser()
    {
        if ($this->mode == self::MODE_PARSEDOWN_EXTRA) {
            return new ParsedownExtra();
        }
        if ($this->mode == self::MODE_PARSEDOWN) {
            return new Parsedown();
        }
        return null;
    }
}
