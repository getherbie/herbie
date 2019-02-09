<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie\sysplugin\dummy;

use Herbie\EventInterface;
use Herbie\FilterInterface;
use Herbie\PluginInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class DummyPlugin implements PluginInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * TestPlugin constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function attach(): void
    {
        $this->logger->debug(__METHOD__);
    }

    /**
     * @return array
     */
    public function getEvents(): array
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
     * @return array
     */
    public function getFilters(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['renderSegment', [$this, 'renderSegment']],
            ['renderContent', [$this, 'renderContent']],
            ['renderLayout', [$this, 'renderLayout']]
        ];
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            [$this, 'dummyMiddleware']
        ];
    }

    /**
     * @return array
     */
    public function getTwigFilters(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['test', [$this, 'twigDummyFilter']]
        ];
    }

    /**
     * @return array
     */
    public function getTwigFunctions(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['test', [$this, 'twigDummyFunction']]
        ];
    }

    /**
     * @return array
     */
    public function getTwigTests(): array
    {
        $this->logger->debug(__METHOD__);
        return [
            ['test', [$this, 'twigDummyTest']]
        ];
    }

    public function renderSegment(string $context, array $params, FilterInterface $filter)
    {
        $this->logger->debug(__METHOD__);
        return $filter->next($context, $params, $filter);
    }

    public function renderContent(array $context, array $params, FilterInterface $filter)
    {
        $this->logger->debug(__METHOD__);
        return $filter->next($context, $params, $filter);
    }

    public function renderLayout(string $context, array $params, FilterInterface $filter)
    {
        $this->logger->debug(__METHOD__);
        return $filter->next($context, $params, $filter);
    }

    public function onContentRendered(EventInterface $event)
    {
        $this->logger->debug(__METHOD__);
    }

    public function onLayoutRendered(EventInterface $event)
    {
        $this->logger->debug(__METHOD__);
    }

    public function onPluginsAttached(EventInterface $event)
    {
        $this->logger->debug(__METHOD__);
    }

    public function onResponseEmitted(EventInterface $event)
    {
        $this->logger->debug(__METHOD__);
    }

    public function onResponseGenerated(EventInterface $event)
    {
        $this->logger->debug(__METHOD__);
    }

    public function onTwigInitialized(EventInterface $event)
    {
        $this->logger->debug(__METHOD__);
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $next
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function dummyMiddleware(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $this->logger->debug(__METHOD__);
        $request = $request->withAttribute('X-TestPlugin', time());
        $response = $next->handle($request);
        return $response->withHeader('X-TestPlugin', time());
    }

    public function twigDummyFilter()
    {
        $this->logger->debug(__METHOD__);
    }

    public function twigDummyFunction()
    {
        $this->logger->debug(__METHOD__);
    }

    public function twigDummyTest()
    {
        $this->logger->debug(__METHOD__);
    }
}
