<?php

declare(strict_types=1);

namespace herbie;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tebe\HttpFactory\HttpFactory;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    private TwigRenderer $twigRenderer;

    /**
     * ErrorHandlerMiddleware constructor.
     */
    public function __construct(TwigRenderer $twigRenderer)
    {
        $this->twigRenderer = $twigRenderer;
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        set_error_handler($this->createErrorHandler());

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            if (!$this->twigRenderer->isInitialized()) {
                $this->twigRenderer->init();
            }
            $content = $this->twigRenderer->renderTemplate('error.twig', [
                'error' => $e
            ]);

            $code = $e->getCode();
            if (empty($code)) {
                $code = 500;
            }

            $response = HttpFactory::instance()->createResponse($code);
            $response->getBody()->write($content);

            error_log($e->getMessage());
        }

        restore_error_handler();

        return $response;
    }

    /**
     * Creates and returns a callable error handler that raises exceptions.
     *
     * Only raises exceptions for errors that are within the error_reporting mask.
     */
    private function createErrorHandler(): callable
    {
        return function (int $errno, string $errstr, string $errfile, int $errline): void {
            if (! (error_reporting() & $errno)) {
                // error_reporting does not include this error
                return;
            }

            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        };
    }
}
