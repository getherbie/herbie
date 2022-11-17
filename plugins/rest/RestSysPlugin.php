<?php

declare(strict_types=1);

namespace herbie\sysplugin\rest;

use herbie\Config;
use herbie\event\RenderSegmentEvent;
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

    public function onRenderSegment(RenderSegmentEvent $event): void
    {
        if ($event->getFormatter() === 'rest') {
            $event->setSegment($this->parseRest($event->getSegment()));
        }
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
