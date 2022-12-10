<?php

declare(strict_types=1);

namespace herbie\sysplugin\textile;

use herbie\Config;
use herbie\event\RenderSegmentEvent;
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
            ['h_textile', [$this, 'parseTextile'], ['is_safe' => ['html']]],
        ];
    }

    public function twigFunctions(): array
    {
        if (!$this->config->getAsBool('enableTwigFunction')) {
            return [];
        }
        return [
            ['h_textile', [$this, 'parseTextile'], ['is_safe' => ['html']]],
        ];
    }

    public function onRenderSegment(RenderSegmentEvent $event): void
    {
        if ($event->getPage()->getFormat() === 'textile') {
            $event->setSegment($this->parseTextile($event->getSegment()));
        }
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
