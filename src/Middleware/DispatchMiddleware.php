<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie\Middleware;

use Herbie\Application;
use Herbie\Page;
use Herbie\StringValue;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DispatchMiddleware implements MiddlewareInterface
{
    protected $herbie;
    protected $pluginManager;

    public function __construct(Application $herbie)
    {
        $this->herbie = $herbie;
        $this->pluginManager = $herbie->getPluginManager();
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $page = $request->getAttribute(Page::class, false);

        if (!$page) {
            $message = sprintf('Server request attribute "%s" doesn\'t exist', Page::class);
            throw new \Exception($message);
        }

        return $this->renderPage($page);
    }

    /**
     * @param Page $page
     * @return ResponseInterface
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function renderPage(Page $page): ResponseInterface
    {
        $rendered = null;

        $cacheId = 'page-' . $this->herbie->getEnvironment()->getRoute();
        if (empty($page->nocache)) {
            $rendered = $this->herbie->getPageCache()->get($cacheId);
        }

        if (null === $rendered) {
            $content = new StringValue();

            try {
                if (empty($page->layout)) {
                    $content = $page->getSegment('0');
                    $this->pluginManager->trigger('renderContent', $content, $page->getData());
                } else {
                    $this->pluginManager->trigger('renderLayout', $content, ['page' => $page]);
                }
            } catch (\Throwable $t) {
                $page->setError($t);
                $this->pluginManager->trigger('renderLayout', $content, ['page' => $page]);
            }

            if (empty($page->nocache)) {
                $this->herbie->getPageCache()->set($cacheId, $content->get());
            }
            $rendered = $content->get();
        }

        $response = $this->herbie->getHttpFactory()->createResponse($page->getStatusCode());
        $response->getBody()->write($rendered);
        $response->withHeader('Content-Type', $page->content_type);

        return $response;
    }
}
