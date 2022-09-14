<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie\sysplugin;

use herbie\EventInterface;
use herbie\FilterInterface;
use herbie\PluginInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

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
            ['onContentRendered', [$this, 'onContentRendered']],
            ['onLayoutRendered', [$this, 'onLayoutRendered']],
            ['onPluginsAttached', [$this, 'onPluginsAttached']],
            ['onResponseEmitted', [$this, 'onResponseEmitted']],
            ['onResponseGenerated', [$this, 'onResponseGenerated']],
            ['onTwigInitialized', [$this, 'onTwigInitialized']],
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

    private function wrapHtmlComment(string $class, string $content): string
    {
        return "<div class='$class' style='display:none'>" . $content . "</div>";
    }
    
    public function renderSegment(string $context, array $params, FilterInterface $filter): string
    {
        $this->logger->debug(__METHOD__);
        $context .= $this->wrapHtmlComment('dummy-plugin-render-segment', __METHOD__);
        return $filter->next($context, $params, $filter);
    }

    public function renderContent(array $context, array $params, FilterInterface $filter): array
    {
        $this->logger->debug(__METHOD__);
        foreach ($context as $key => $value) {
            $context[$key] = $context[$key] . $this->wrapHtmlComment('dummy-plugin-render-content', __METHOD__);
        }
        return $filter->next($context, $params, $filter);
    }

    public function renderLayout(string $context, array $params, FilterInterface $filter): string
    {
        $this->logger->debug(__METHOD__);
        $context = str_replace(
            '</body>',
            $this->wrapHtmlComment('dummy-plugin-render-layout', __METHOD__) . '</body>',
            $context
        );
        return $filter->next($context, $params, $filter);
    }

    public function onContentRendered(EventInterface $event): void
    {
        $this->logger->debug(__METHOD__);
    }

    public function onLayoutRendered(EventInterface $event): void
    {
        $this->logger->debug(__METHOD__);
    }

    public function onPluginsAttached(EventInterface $event): void
    {
        $this->logger->debug(__METHOD__);
    }

    public function onResponseEmitted(EventInterface $event): void
    {
        $this->logger->debug(__METHOD__);
    }

    public function onResponseGenerated(EventInterface $event): void
    {
        $this->logger->debug(__METHOD__);
    }

    public function onTwigInitialized(EventInterface $event): void
    {
        $this->logger->debug(__METHOD__);
    }

    public function dummyMiddleware(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $this->logger->debug(__METHOD__);
        $request = $request->withAttribute('X-Plugin-Dummy', (string)time());
        $response = $next->handle($request);
        return $response->withHeader('X-Plugin-Dummy', (string)time());
    }

    public function twigDummyFilter(string $content): string
    {
        $this->logger->debug(__METHOD__);
        return $content;
    }

    public function twigDummyFunction(string $content): string
    {
        $this->logger->debug(__METHOD__);
        return $content;
    }

    public function twigDummyTest(string $content): bool
    {
        $this->logger->debug(__METHOD__);
        return strlen($content) > 0;
    }
}
