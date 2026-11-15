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
            $contentType = $this->resolveContentType($request);

            if ($this->isXml($contentType)) {
                $content = $this->renderXmlError($e);
            } else {
                if (!$this->twigRenderer->isInitialized()) {
                    $this->twigRenderer->init();
                }
                $content = $this->twigRenderer->renderTemplate('error.twig', ['error' => $e]);
            }

            $code = $e->getCode();
            if (empty($code)) {
                $code = 500;
            }

            $response = $this->responseFactory
                ->createResponse($code)
                ->withHeader('Content-Type', $contentType);
            $response->getBody()->write($content);

            error_log($e->getMessage());
        }

        restore_error_handler();

        return $response;
    }

    /**
     * Determines the Content-Type of the response the request would have produced.
     *
     * The page itself is the most reliable source. It is looked up here instead of reading
     * the request attribute, because a middleware that ran earlier received a new request
     * object and the error handler only sees the original one. If no page matches, the
     * Accept header of the client decides.
     */
    private function resolveContentType(ServerRequestInterface $request): string
    {
        try {
            [$route] = $this->urlManager->parseRequest();
            $page = $this->pageRepository->findAll()->getItem($route);
        } catch (Throwable) {
            $page = null;
        }

        if ($page instanceof Page) {
            $contentType = $page->getContentType();
            if ($contentType !== '') {
                return $contentType;
            }
        }

        return $this->acceptsXml($request)
            ? self::XML_CONTENT_TYPE
            : self::DEFAULT_CONTENT_TYPE;
    }

    private function acceptsXml(ServerRequestInterface $request): bool
    {
        $accept = $request->getHeaderLine('Accept');
        if ($accept === '') {
            return false;
        }
        return (strpos($accept, 'xml') !== false) && (strpos($accept, 'html') === false);
    }

    private function isXml(string $contentType): bool
    {
        $contentType = strtolower($contentType);
        return (strpos($contentType, 'xml') !== false) || (strpos($contentType, 'rss') !== false);
    }

    /**
     * Renders a minimal XML error document.
     *
     * The Twig error template produces HTML, which would make the response unparsable for a
     * client that expects XML.
     */
    private function renderXmlError(Throwable $e): string
    {
        $code = $e->getCode();
        if (empty($code)) {
            $code = 500;
        }

        $document = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><error/>');
        $document->addChild('code', (string)$code);
        $document->addChild('message', $this->escapeXml($e->getMessage()));

        return $document->asXML() ?: '';
    }

    /**
     * Escapes a string for XML and removes characters that are not allowed in XML 1.0.
     *
     * An error message can contain arbitrary bytes, for example when it quotes a binary
     * string. Such characters would make the error document itself unparsable.
     */
    private function escapeXml(string $value): string
    {
        // Allowed in XML 1.0: tab, line feed, carriage return, and everything from 0x20 on.
        $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{10FFFF}]/u', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1);
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
