<?php

declare(strict_types=1);

namespace herbie\middlewares;

use ErrorException;
use herbie\Page;
use herbie\PageRepositoryInterface;
use herbie\TwigRenderer;
use herbie\UrlManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final readonly class ErrorHandlerMiddleware implements MiddlewareInterface
{
    private const string DEFAULT_CONTENT_TYPE = 'text/html';

    private const string XML_CONTENT_TYPE = 'application/xml';

    /**
     * ErrorHandlerMiddleware constructor.
     */
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ResponseFactoryInterface $responseFactory,
        private TwigRenderer $twigRenderer,
        private UrlManager $urlManager
    ) {
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     */
    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        set_error_handler($this->createErrorHandler());

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            $bodyContentType = $this->resolveContentType($request);
            if (!$this->isXml($bodyContentType)) {
                // the only non XML representation available is the HTML error template
                $bodyContentType = self::DEFAULT_CONTENT_TYPE;
            }

            if ($bodyContentType === self::DEFAULT_CONTENT_TYPE) {
                if (!$this->twigRenderer->isInitialized()) {
                    $this->twigRenderer->init();
                }
                $content = $this->twigRenderer->renderTemplate('error.twig', ['error' => $e]);
            } else {
                $content = $this->renderXmlError($e);
            }

            $code = $e->getCode();
            if (empty($code)) {
                $code = 500;
            }

            $response = $this->responseFactory
                ->createResponse($code)
                ->withHeader('Content-Type', $bodyContentType);
            $response->getBody()->write($content);

            error_log($e->getMessage());
        }

        restore_error_handler();

        return $response;
    }

    /**
     * Returns the Content-Type the response would have had.
     *
     * The page is looked up here rather than read from the request attribute: a middleware
     * in front passes an enriched copy of the request down the chain, so the error handler
     * only ever sees the original object. Without a matching page the Accept header decides.
     */
    private function resolveContentType(ServerRequestInterface $request): string
    {
        try {
            [$route] = $this->urlManager->parseRequest();
            $page = $this->pageRepository->findAll()->getItem($route);
            if ($page instanceof Page && $page->getContentType() !== '') {
                return $page->getContentType();
            }
        } catch (Throwable) {
            // fall through to the Accept header
        }

        $accept = $request->getHeaderLine('Accept');
        $wantsXml = $accept !== '' && str_contains($accept, 'xml') && !str_contains($accept, 'html');

        return $wantsXml ? self::XML_CONTENT_TYPE : self::DEFAULT_CONTENT_TYPE;
    }

    private function isXml(string $contentType): bool
    {
        $contentType = strtolower($contentType);
        return str_contains($contentType, 'xml') || str_contains($contentType, 'rss');
    }

    /**
     * Renders a minimal XML error document.
     *
     * The Twig template produces HTML, which a client expecting XML cannot parse. Characters
     * that XML forbids are removed, otherwise a message quoting binary data would break the
     * document itself.
     */
    private function renderXmlError(Throwable $e): string
    {
        $code = empty($e->getCode()) ? 500 : (int)$e->getCode();
        $message = preg_replace('/[^\x09\x0A\x0D\x20-\x{10FFFF}]/u', '', $e->getMessage()) ?? '';

        return sprintf(
            "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<error><code>%d</code><message>%s</message></error>\n",
            $code,
            htmlspecialchars($message, ENT_XML1)
        );
    }

    /**
     * Creates and returns a callable error handler that raises exceptions.
     *
     * Only raises exceptions for errors that are within the error_reporting mask.
     */
    private function createErrorHandler(): callable
    {
        return function (int $errno, string $errstr, string $errfile, int $errline): void {
            if (!(error_reporting() & $errno)) {
                // error_reporting does not include this error
                return;
            }

            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        };
    }
}
