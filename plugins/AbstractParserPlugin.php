<?php

declare(strict_types=1);

namespace herbie\plugins;

use herbie\AbstractPlugin;
use herbie\Config;
use herbie\events\RenderSegmentEvent;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Base class for the system plugins that convert a page format to HTML.
 *
 * A subclass defines the name of the format, the name of the twig filter and function,
 * and how a segment is converted. The event listener, the twig filter and the twig
 * function are set up once for all of them.
 */
abstract class AbstractParserPlugin extends AbstractPlugin
{
    protected Config $config;

    /**
     * @param string $configPath The path of the plugin configuration, e.g. "plugins.textile"
     */
    public function __construct(Config $config, protected LoggerInterface $logger, string $configPath)
    {
        $this->config = $config->getAsConfig($configPath);
    }

    /**
     * The page formats, that this plugin converts, e.g. ["textile"]
     *
     * @return string[]
     */
    abstract protected function getFormats(): array;

    /**
     * The name of the twig filter and function, e.g. "textile"
     */
    abstract protected function getTwigName(): string;

    /**
     * Converts the given text to HTML
     */
    abstract public function parse(string $value): string;

    public function eventListeners(): array
    {
        return [
            [RenderSegmentEvent::class, $this->onRenderSegment(...)],
        ];
    }

    public function twigFilters(): array
    {
        if (!$this->config->getAsBool('enableTwigFilter')) {
            return [];
        }
        return [
            [$this->getTwigName(), $this->parse(...), ['is_safe' => ['html']]],
        ];
    }

    public function twigFunctions(): array
    {
        if (!$this->config->getAsBool('enableTwigFunction')) {
            return [];
        }
        return [
            [$this->getTwigName(), $this->parse(...), ['is_safe' => ['html']]],
        ];
    }

    public function onRenderSegment(RenderSegmentEvent $event): void
    {
        if (in_array($event->getPage()->getFormat(), $this->getFormats(), true)) {
            $event->setSegment($this->parse($event->getSegment()));
        }
    }

    /**
     * Converts the text, but returns it unchanged if the conversion fails.
     */
    protected function parseSafely(string $value, callable $parse): string
    {
        try {
            return $parse($value);
        } catch (Throwable $t) {
            $this->logger->error(sprintf(
                'The %s plugin could not convert the segment: %s',
                $this->getTwigName(),
                $t->getMessage(),
            ));
            return $value;
        }
    }
}
