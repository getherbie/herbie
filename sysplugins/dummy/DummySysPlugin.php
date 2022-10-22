<?php

declare(strict_types=1);

namespace herbie\sysplugin\dummy;

use herbie\EventInterface;
use herbie\FilterInterface;
use herbie\Page;
use herbie\PluginInterface;
use herbie\TwigRenderer;
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

    public function commands(): array
    {
        return [
            DummyCommand::class,
        ];
    }

    public function events(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['onContentRendered', [$this, 'onGenericEventHandler']],
            ['onLayoutRendered', [$this, 'onGenericEventHandler']],
            ['onPluginsAttached', [$this, 'onGenericEventHandler']],
            ['onResponseEmitted', [$this, 'onGenericEventHandler']],
            ['onResponseGenerated', [$this, 'onGenericEventHandler']],
            ['onTwigInitialized', [$this, 'onGenericEventHandler']],
            ['onTwigInitialized', [$this, 'onTwigInitializedEventHandler']],
            ['onSystemPluginsAttached', [$this, 'onGenericEventHandler']],
            ['onComposerPluginsAttached', [$this, 'onGenericEventHandler']],
            ['onLocalPluginsAttached', [$this, 'onGenericEventHandler']],
        ];
    }

    public function filters(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['renderSegment', [$this, 'renderSegment']],
            ['renderContent', [$this, 'renderContent']],
            ['renderLayout', [$this, 'renderLayout']]
        ];
    }

    public function appMiddlewares(): array
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
            'dummy' => $this
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

    /**
     * @param array{page: Page, routeParams: array<string, mixed>} $params
     */
    public function renderSegment(string $context, array $params, FilterInterface $filter): string
    {
        $this->logger->debug(__METHOD__);
        $context .= $this->wrapHtmlBlock('dummy-plugin-render-segment', __METHOD__);
        return $filter->next($context, $params, $filter);
    }

    /**
     * @param array<string, string> $context
     * @param array{page: Page, routeParams: array<string, mixed>} $params
     * @return array<string, string>
     */
    public function renderContent(array $context, array $params, FilterInterface $filter): array
    {
        $this->logger->debug(__METHOD__);
        foreach ($context as $key => $value) {
            $context[$key] = $value . $this->wrapHtmlBlock('dummy-plugin-render-content', __METHOD__);
        }
        return $filter->next($context, $params, $filter);
    }

    /**
     * @param array{content: array<string, string>, page: Page, routeParams: array<string, mixed>} $params
     */
    public function renderLayout(string $context, array $params, FilterInterface $filter): string
    {
        $this->logger->debug(__METHOD__);
        $context = str_replace(
            '</body>',
            $this->wrapHtmlBlock('dummy-plugin-render-layout', __METHOD__) . '</body>',
            $context
        );
        return $filter->next($context, $params, $filter);
    }

    public function onGenericEventHandler(EventInterface $event): void
    {
        $this->logger->debug('Event ' . $event->getName() . ' was triggered');
    }

    public function onTwigInitializedEventHandler(EventInterface $event): void
    {
        $this->logger->debug(__METHOD__);
        /** @var TwigRenderer $twigRenderer */
        $twigRenderer = $event->getTarget();
        $twigRenderer->addFilter(new TwigFilter('dummy_dynamic', function (string $content): string {
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
        return strlen($content) > 0;
    }
}
