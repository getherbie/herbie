<?php

declare(strict_types=1);

namespace herbie\sysplugins\markdown;

use herbie\Config;
use herbie\events\RenderSegmentEvent;
use herbie\Plugin;
use herbie\sysplugins\markdown\HerbieParsedownExtra;
use Parsedown;
use Psr\Log\LoggerInterface;

final class MarkdownSysPlugin extends Plugin
{
    private const MODE_NONE = 0;
    private const MODE_PARSEDOWN = 1;
    private const MODE_PARSEDOWN_EXTRA = 2;

    private int $mode;

    /**
     * MarkdownPlugin constructor.
     */
    public function __construct(
        private Config $config,
        LoggerInterface $logger
    ) {
        $this->config = $config->getAsConfig('plugins.markdown');
        if (class_exists('ParsedownExtra')) {
            $this->mode = self::MODE_PARSEDOWN_EXTRA;
        } elseif (class_exists('Parsedown')) {
            $this->mode = self::MODE_PARSEDOWN;
        } else {
            $this->mode = self::MODE_NONE;
            $logger->error('Please install either "erusev/parsedown" or "erusev/parsedown-extra" via composer');
        }
    }

    public function eventListeners(): array
    {
        return [
            [RenderSegmentEvent::class, [$this, 'onRenderSegment']]
        ];
    }

    public function twigFilters(): array
    {
        if (!$this->config->getAsBool('enableTwigFilter')) {
            return [];
        }
        return [
            ['markdown', [$this, 'parseMarkdown'], ['is_safe' => ['html']]],
        ];
    }

    public function twigFunctions(): array
    {
        if (!$this->config->getAsBool('enableTwigFunction')) {
            return [];
        }
        return [
            ['markdown', [$this, 'parseMarkdown'], ['is_safe' => ['html']]],
        ];
    }

    public function onRenderSegment(RenderSegmentEvent $event): void
    {
        if (in_array($event->getPage()->getFormat(), ['markdown', 'md'], true)) {
            $event->setSegment($this->parseMarkdown($event->getSegment()));
        }
    }

    public function parseMarkdown(string $string): string
    {
        $parser = $this->createParser();
        if ($parser === null) {
            return $string;
        }
        $parser->setUrlsLinked(false);
        try {
            return $parser->text($string);
        } catch (\Throwable $e) {
            // ParsedownExtra can throw ValueError on empty DOMDocument input (PHP 8.3+)
            return $string;
        }
    }

    /**
     * @return Parsedown|HerbieParsedownExtra|null
     */
    private function createParser()
    {
        if ($this->mode === self::MODE_PARSEDOWN_EXTRA) {
            return new HerbieParsedownExtra();
        }
        if ($this->mode === self::MODE_PARSEDOWN) {
            return new Parsedown();
        }
        return null;
    }
}
