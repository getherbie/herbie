<?php

declare(strict_types=1);

namespace herbie\sysplugins\dummy;

use herbie\events\ContentRenderedEvent;
use herbie\events\LayoutRenderedEvent;
use herbie\events\PluginsInitializedEvent;
use herbie\events\RenderLayoutEvent;
use herbie\events\RenderSegmentEvent;
use herbie\events\ResponseEmittedEvent;
use herbie\events\ResponseGeneratedEvent;
use herbie\events\TranslatorInitializedEvent;
use herbie\events\TwigInitializedEvent;
use herbie\PluginInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Twig\TwigFilter;

final class DummySysPlugin implements PluginInterface
{
    private LoggerInterface $logger;

    /**
     * DummyPlugin constructor.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function apiVersion(): int
    {
        return 2;
    }

    public function consoleCommands(): array
    {
        return [
            DummyCommand::class,
        ];
    }

    public function eventListeners(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            [ContentRenderedEvent::class, [$this, 'onContentRendered']],
            [LayoutRenderedEvent::class, [$this, 'onLayoutRendered']],
            [PluginsInitializedEvent::class, [$this, 'onPluginsInitialized']],
            [RenderLayoutEvent::class, [$this, 'onRenderLayout']],
            [RenderSegmentEvent::class, [$this, 'onRenderSegment']],
            [ResponseEmittedEvent::class, [$this, 'onResponseEmitted']],
            [ResponseGeneratedEvent::class, [$this, 'onResponseGenerated']],
            [TranslatorInitializedEvent::class, [$this, 'onTranslatorInitialized']],
            [TwigInitializedEvent::class, [$this, 'onTwigInitialized']],
            [TwigInitializedEvent::class, [$this, 'onTwigInitializedAddFilter']],
        ];
    }

    public function applicationMiddlewares(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            [$this, 'appMiddleware']
        ];
    }

    public function routeMiddlewares(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['download/dummy.pdf', [$this, 'routeMiddleware']],
            ['plugins/dummy', [$this, 'routeMiddleware']]
        ];
    }

    public function twigFilters(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['dummy', [$this, 'twigDummyFilter']]
        ];
    }

    public function twigGlobals(): array
    {
        return [
            ['dummy', $this]
        ];
    }

    public function twigFunctions(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['dummy', [$this, 'twigDummyFunction']]
        ];
    }

    public function twigTests(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['dummy', [$this, 'twigDummyTest']]
        ];
    }

    private function wrapHtmlBlock(string $class, string $content): string
    {
        return "<div class='$class' style='display:none'>" . $content . "</div>";
    }

    public function onRenderLayout(RenderLayoutEvent $event): void
    {
        $this->logger->debug(__METHOD__);
        $content = str_replace(
            '</body>',
            $this->wrapHtmlBlock('dummy-plugin-render-layout', __METHOD__) . '</body>',
            $event->getContent(),
        );
        $event->setContent($content);
    }

    public function onRenderSegment(RenderSegmentEvent $event): void
    {
        $this->logger->debug(__METHOD__);
        $segment = $event->getSegment()
            . $this->wrapHtmlBlock('dummy-plugin-render-segment', __METHOD__);
        $event->setSegment($segment);
    }

    public function onContentRendered(ContentRenderedEvent $event): void
    {
        $this->logger->debug('Event ' . get_class($event) . ' was triggered');
    }

    public function onLayoutRendered(LayoutRenderedEvent $event): void
    {
        $this->logger->debug('Event ' . get_class($event) . ' was triggered');
    }

    public function onPluginsInitialized(PluginsInitializedEvent $event): void
    {
        $this->logger->debug('Event ' . get_class($event) . ' was triggered');
    }

    public function onResponseEmitted(ResponseEmittedEvent $event): void
    {
        $this->logger->debug('Event ' . get_class($event) . ' was triggered');
    }

    public function onResponseGenerated(ResponseGeneratedEvent $event): void
    {
        $this->logger->debug('Event ' . get_class($event) . ' was triggered');
    }

    public function onTranslatorInitialized(TranslatorInitializedEvent $event): void
    {
        $this->logger->debug('Event ' . get_class($event) . ' was triggered');
    }

    public function onTwigInitialized(TwigInitializedEvent $event): void
    {
        $this->logger->debug('Event ' . get_class($event) . ' was triggered');
    }

    public function onTwigInitializedAddFilter(TwigInitializedEvent $event): void
    {
        $this->logger->debug(__METHOD__);
        $event->getEnvironment()->addFilter(new TwigFilter('dummy_dynamic', function (string $content): string {
            return $content . 'Dummy Filter Dynamic';
        }));
    }

    public function appMiddleware(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $this->logger->debug(__METHOD__);
        $response = $next->handle($request);

        if (!$response->getBody()->isWritable()) {
            // files like pdfs are not writable
            return $response;
        }

        $content = str_replace(
            '</body>',
            $this->wrapHtmlBlock('dummy-plugin-app-middleware', __METHOD__) . '</body>',
            (string)$response->getBody()
        );

        $response->getBody()->rewind();
        $response->getBody()->write($content);
        return $response;
    }

    public function routeMiddleware(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $this->logger->debug(__METHOD__);
        $response = $next->handle($request);

        if (!$response->getBody()->isWritable()) {
            // files like pdfs are not writable
            return $response;
        }

        $content = str_replace(
            '</body>',
            $this->wrapHtmlBlock('dummy-plugin-route-middleware', __METHOD__) . '</body>',
            (string)$response->getBody()
        );

        $response->getBody()->rewind();
        $response->getBody()->write($content);
        return $response;
    }

    public function twigDummyFilter(string $content): string
    {
        $this->logger->debug(__METHOD__);
        $content .= 'Dummy Filter';
        return $content;
    }

    public function twigDummyFunction(string $content): string
    {
        $this->logger->debug(__METHOD__);
        $content .= ' Dummy Function';
        return $content;
    }

    public function twigDummyTest(string $content): bool
    {
        $this->logger->debug(__METHOD__);
        return $content !== '';
    }
}
