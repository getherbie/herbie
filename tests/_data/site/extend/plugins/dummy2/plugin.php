<?php

declare(strict_types=1);

namespace herbie\tests\_data\site\extend\plugins\dummy2;

use herbie\events\ContentRenderedEvent;
use herbie\events\RenderLayoutEvent;
use herbie\events\RenderSegmentEvent;
use herbie\PluginInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final class Dummy2Plugin implements PluginInterface
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
    public function eventListeners(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            [ContentRenderedEvent::class, [$this, 'onContentRendered']],
            [RenderLayoutEvent::class, [$this, 'onRenderLayout']],
            [RenderSegmentEvent::class, [$this, 'onRenderSegment']],
        ];
    }

    /**
     * @return array[]
     */
    public function middlewares(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            [$this, 'dummyMiddleware']
        ];
    }

    /**
     * @return array[]
     */
    public function twigFilters(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['dummy2', [$this, 'twigDummyFilter']]
        ];
    }

    /**
     * @return array[]
     */
    public function twigFunctions(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['dummy2', [$this, 'twigDummyFunction']]
        ];
    }

    /**
     * @return array[]
     */
    public function twigTests(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['dummy2', [$this, 'twigDummyTest']]
        ];
    }

    public function onRenderSegment(RenderSegmentEvent $event): void
    {
        $this->logger->debug(__METHOD__);
        $segment = $event->getSegment()
            . $this->wrapHtmlBlock('dummy2-plugin-render-segment', __METHOD__);
        $event->setSegment($segment);
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
            $this->wrapHtmlBlock('dummy2-plugin-render-layout', __METHOD__) . '</body>',
            $event->getLayout()
        );
        $event->setContent($content);
    }

    public function onContentRendered(ContentRenderedEvent $event): void
    {
        $this->logger->debug(get_class($event));
        // TODO add test
    }

    public function dummyMiddleware(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $this->logger->debug(__METHOD__);
        $request = $request->withAttribute('X-Plugin-Dummy2', (string)time());
        $response = $next->handle($request)
            ->withHeader('X-Plugin-Dummy2', (string)time());

        if ($request->getUri()->getPath() === '/plugins/dummy') {
            $content = (string)$response->getBody();
            $newContent = str_replace('</body>', '<p>This is from Dummy2 Middleware.</p></body', $content);
            $response->getBody()->rewind();
            $response->getBody()->write($newContent);
            return $response;
        }

        return $response;
    }

    public function twigDummyFilter(string $content): string
    {
        $this->logger->debug(__METHOD__);
        $content .= ' Dummy2 Filter';
        return $content;
    }

    public function twigDummyFunction(string $content): string
    {
        $this->logger->debug(__METHOD__);
        $content .= ' Dummy2 Function';
        return $content;
    }

    public function twigDummyTest(string $content): bool
    {
        $this->logger->debug(__METHOD__);
        return $content !== '';
    }

    public function applicationMiddlewares(): array
    {
        return [];
    }

    public function routeMiddlewares(): array
    {
        return [];
    }

    public function consoleCommands(): array
    {
        return [];
    }

    public function twigGlobals(): array
    {
        return [];
    }
}
