<?php

declare(strict_types=1);

namespace herbie\sysplugin;

use herbie\EventInterface;
use herbie\FilterInterface;
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

    /**
     * @return array[]
     */
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

    /**
     * @return array[]
     */
    public function filters(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['renderSegment', [$this, 'renderSegment']],
            ['renderContent', [$this, 'renderContent']],
            ['renderLayout', [$this, 'renderLayout']]
        ];
    }

    /**
     * @return array[]
     */
    public function appMiddlewares(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            [$this, 'appMiddleware']
        ];
    }

    /**
     * @return array[]
     */
    public function routeMiddlewares(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['documentation', [$this, 'routeMiddleware']]
        ];
    }

    /**
     * @return array[]
     */
    public function twigFilters(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['dummy', [$this, 'twigDummyFilter']]
        ];
    }

    /**
     * @return array[]
     */
    public function twigFunctions(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['dummy', [$this, 'twigDummyFunction']]
        ];
    }

    /**
     * @return array[]
     */
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

    public function renderSegment(string $context, array $params, FilterInterface $filter): string
    {
        $this->logger->debug(__METHOD__);
        $context .= $this->wrapHtmlBlock('dummy-plugin-render-segment', __METHOD__);
        return $filter->next($context, $params, $filter);
    }

    public function renderContent(array $context, array $params, FilterInterface $filter): array
    {
        $this->logger->debug(__METHOD__);
        foreach ($context as $key => $value) {
            $context[$key] = $value . $this->wrapHtmlBlock('dummy-plugin-render-content', __METHOD__);
        }
        return $filter->next($context, $params, $filter);
    }

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
            return $content . ' Dummy Filter Dynamic';
        }));
    }

    public function appMiddleware(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $this->logger->debug(__METHOD__);
        $request = $request->withAttribute('X-Plugin-Dummy', (string)time());
        return $next->handle($request)->withHeader('X-Plugin-Dummy', (string)time());
    }

    public function routeMiddleware(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $this->logger->debug(__METHOD__);
        $response = $next->handle($request);
        $content = (string)$response->getBody();
        $newContent = str_replace(
            '</body>',
            $this->wrapHtmlBlock('dummy-plugin-route-middleware', __METHOD__) . '</body>',
            $content
        );
        $response->getBody()->rewind();
        $response->getBody()->write($newContent);
        return $response;
    }

    public function twigDummyFilter(string $content): string
    {
        $this->logger->debug(__METHOD__);
        $content .= ' Dummy Filter';
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
