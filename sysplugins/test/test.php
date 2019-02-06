<?php

namespace herbie\plugin\test;

use Herbie\Event;
use Herbie\Plugin;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Zend\EventManager\Filter\FilterIterator;

class TestPlugin extends Plugin
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

    /**
     * @return array
     */
    public function getEvents(): array
    {
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
        return [
            [$this, 'testMiddleware']
        ];
    }

    /**
     * @return array
     */
    public function getTwigFilters(): array
    {
        return [
            ['test', [$this, 'twigTestFilter']]
        ];
    }

    /**
     * @return array
     */
    public function getTwigFunctions(): array
    {
        return [
            ['test', [$this, 'twigTestFunction']]
        ];
    }

    /**
     * @return array
     */
    public function getTwigTests(): array
    {
        return [
            ['test', [$this, 'twigTestTest']]
        ];
    }

    public function renderSegment(string $context, array $params, FilterIterator $chain)
    {
        $this->logger->info(__METHOD__);
        return $chain->next($context, $params, $chain);
    }

    public function renderContent(array $context, array $params, FilterIterator $chain)
    {
        $this->logger->info(__METHOD__);
        return $chain->next($context, $params, $chain);
    }

    public function renderLayout(string $context, array $params, FilterIterator $chain)
    {
        $this->logger->info(__METHOD__);
        return $chain->next($context, $params, $chain);
    }

    public function onContentRendered(Event $event)
    {
        $this->logger->info($event->getName());
    }

    public function onLayoutRendered(Event $event)
    {
        $this->logger->info($event->getName());
    }

    public function onPluginsAttached(Event $event)
    {
        $this->logger->info($event->getName());
    }

    public function onResponseEmitted(Event $event)
    {
        $this->logger->info($event->getName());
    }

    public function onResponseGenerated(Event $event)
    {
        $this->logger->info($event->getName());
    }

    public function onTwigInitialized(Event $event)
    {
        $this->logger->info($event->getName());
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $next
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function testMiddleware(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $this->logger->info(__METHOD__);
        $request = $request->withAttribute('X-TestPlugin', time());
        $response = $next->handle($request);
        return $response->withHeader('X-TestPlugin', time());
    }

    public function twigTestFilter()
    {
        $this->logger->info(__METHOD__);
    }

    public function twigTestFunction()
    {
        $this->logger->info(__METHOD__);
    }

    public function twigTestTest()
    {
        $this->logger->info(__METHOD__);
    }
}
