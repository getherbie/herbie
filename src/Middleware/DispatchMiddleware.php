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
use Herbie\Hook;
use Herbie\Page;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DispatchMiddleware implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
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
     */
    protected function renderPage(Page $page): ResponseInterface
    {
        $rendered = false;

        $cacheId = 'page-' . Application::getService('Environment')->getRoute();
        if (empty($page->nocache)) {
            $rendered = Application::getService('Cache\PageCache')->get($cacheId);
        }

        if (false === $rendered) {
            $content = new \stdClass();
            $content->string = '';

            try {
                if (empty($page->layout)) {
                    $content = $page->getSegment('0');
                    $content->string = Hook::trigger(Hook::FILTER, 'renderContent', $content->string, $page->getData());
                } else {
                    $content->string = Hook::trigger(Hook::FILTER, 'renderLayout', $page);
                }
            } catch (\Throwable $t) {
                $page->setError($t);
                $content->string = Hook::trigger(Hook::FILTER, 'renderLayout', $page);
            }

            if (empty($page->nocache)) {
                Application::getService('Cache\PageCache')->set($cacheId, $content->string);
            }
            $rendered = $content->string;
        }

        $response = Application::getService('HttpFactory')->createResponse($page->getStatusCode());
        $response->getBody()->write($rendered);
        $response->withHeader('Content-Type', $page->content_type);

        return $response;
    }
}
