<?php

declare(strict_types=1);

namespace tests\_data\site\extend\plugins\dummy2;

use herbie\EventInterface;
use herbie\FilterInterface;
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
    public function events(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['onContentRendered', [$this, 'onContentRendered']],
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
            ['renderLayout', [$this, 'renderLayout']]
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

    private function wrapHtmlBlock(string $class, string $content): string
    {
        return "<div class='$class' style='display:none'>" . $content . "</div>";
    }

    public function renderSegment(string $context, array $params, FilterInterface $filter): string
    {
        $this->logger->debug(__METHOD__);
        $context .= $this->wrapHtmlBlock('dummy2-plugin-render-segment', __METHOD__);
        return $filter->next($context, $params, $filter);
    }

    public function renderLayout(string $context, array $params, FilterInterface $filter): string
    {
        $this->logger->debug(__METHOD__);
        $context = str_replace(
            '</body>',
            $this->wrapHtmlBlock('dummy2-plugin-render-layout', __METHOD__) . '</body>',
            $context
        );
        return $filter->next($context, $params, $filter);
    }

    public function onContentRendered(EventInterface $event): void
    {
        $this->logger->debug(__METHOD__);
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
        return strlen($content) > 0;
    }

    public function appMiddlewares(): array
    {
        // TODO: Implement appMiddlewares() method.
    }

    public function routeMiddlewares(): array
    {
        // TODO: Implement routeMiddlewares() method.
    }
}
