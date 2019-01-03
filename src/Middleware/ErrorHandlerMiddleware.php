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

use ErrorException;
use Herbie\Page;
use Herbie\PluginManager;
use Herbie\StringValue;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tebe\HttpFactory\HttpFactory;

class ErrorHandlerMiddleware implements MiddlewareInterface
{
    /**
     * @var PluginManager
     */
    protected $events;

    /**
     * ErrorHandlerMiddleware constructor.
     * @param PluginManager $events
     */
    public function __construct(PluginManager $events)
    {
        $this->events = $events;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        set_error_handler($this->createErrorHandler());

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $e) {
            $string = new StringValue();

            $page = new Page();
            $page->layout = 'error';
            $page->setError($e);

            $this->events->trigger('onRenderLayout', $string, ['page' => $page]);

            $code = $e->getCode();
            if (empty($code)) {
                $code = 500;
            }

            $response = HttpFactory::instance()->createResponse($code);
            $response->getBody()->write(strval($string));
        }

        restore_error_handler();

        return $response;
    }

    /**
     * Creates and returns a callable error handler that raises exceptions.
     *
     * Only raises exceptions for errors that are within the error_reporting mask.
     *
     * @return callable
     */
    protected function createErrorHandler() : callable
    {
        /**
         * @param int $errno
         * @param string $errstr
         * @param string $errfile
         * @param int $errline
         */
        return function (int $errno, string $errstr, string $errfile, int $errline) : void {
            if (! (error_reporting() & $errno)) {
                // error_reporting does not include this error
                return;
            }

            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        };
    }
}
