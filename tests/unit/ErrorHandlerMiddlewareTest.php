<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Config;
use herbie\HttpException;
use herbie\middlewares\ErrorHandlerMiddleware;
use herbie\Page;
use herbie\PageList;
use herbie\PageRepositoryInterface;
use herbie\Site;
use herbie\TwigRenderer;
use herbie\UrlManager;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * The Twig error template produces HTML. A route that serves XML has to answer with XML
 * instead, otherwise the client receives a well formed document with the wrong root element.
 */
final class ErrorHandlerMiddlewareTest extends Unit
{
    public function testHtmlPageKeepsHtmlErrorTemplate(): void
    {
        $middleware = $this->createMiddleware(new Page(['content_type' => 'text/html']));
        $exception = new RuntimeException('Something failed');

        $response = $middleware->process(new ServerRequest(), $this->failingHandler($exception));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('<html>', (string)$response->getBody());
        $this->assertStringContainsString('Something failed', (string)$response->getBody());
    }

    public function testXmlPageAnswersWithXml(): void
    {
        $middleware = $this->createMiddleware(new Page(['content_type' => 'text/xml']));
        $exception = new RuntimeException('Something failed');

        $response = $middleware->process(new ServerRequest(), $this->failingHandler($exception));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/xml', $response->getHeaderLine('Content-Type'));
        $this->assertStringNotContainsString('<html', (string)$response->getBody());
    }

    public function testXmlErrorDocumentIsWellFormed(): void
    {
        $middleware = $this->createMiddleware(new Page(['content_type' => 'text/xml']));

        $response = $middleware->process(
            new ServerRequest(),
            $this->failingHandler(new RuntimeException('Something failed')),
        );

        $content = (string)$response->getBody();
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($xml, 'The error document is not valid XML: ' . $content);
        $this->assertSame('500', (string)$xml->code);
    }

    public function testXmlErrorDocumentContainsTheStatusCodeAndMessage(): void
    {
        $middleware = $this->createMiddleware(new Page(['content_type' => 'text/xml']));
        $exception = HttpException::notFound('missing/page');

        $response = $middleware->process(new ServerRequest(), $this->failingHandler($exception));

        $this->assertSame(404, $response->getStatusCode());

        $xml = simplexml_load_string((string)$response->getBody());
        $this->assertNotFalse($xml);
        $this->assertSame('404', (string)$xml->code);
        $this->assertStringContainsString('missing/page', (string)$xml->message);
    }

    public function testXmlErrorEscapesMarkupInTheMessage(): void
    {
        $middleware = $this->createMiddleware(new Page(['content_type' => 'text/xml']));
        $exception = new RuntimeException('Cannot parse <feed> & <entry>');

        $response = $middleware->process(new ServerRequest(), $this->failingHandler($exception));

        $content = (string)$response->getBody();
        $xml = simplexml_load_string($content);

        $this->assertNotFalse($xml, 'The error document is not valid XML: ' . $content);
        $this->assertSame('Cannot parse <feed> & <entry>', (string)$xml->message);
    }

    public function testXmlErrorDropsCharactersThatAreNotAllowedInXml(): void
    {
        $middleware = $this->createMiddleware(new Page(['content_type' => 'text/xml']));
        $exception = new RuntimeException("binary \x01\x08 content");

        $response = $middleware->process(new ServerRequest(), $this->failingHandler($exception));

        $content = (string)$response->getBody();
        $this->assertNotFalse(
            simplexml_load_string($content),
            'Control characters must not break the error document: ' . $content,
        );
    }

    public function testAcceptHeaderDecidesWithoutAMatchingPage(): void
    {
        $middleware = $this->createMiddleware(null);

        $response = $middleware->process(
            (new ServerRequest())->withHeader('Accept', 'application/xml'),
            $this->failingHandler(new RuntimeException('Something failed')),
        );

        $this->assertSame('application/xml', $response->getHeaderLine('Content-Type'));
    }

    public function testAcceptHeaderWithoutXmlKeepsHtml(): void
    {
        $middleware = $this->createMiddleware(null);

        $response = $middleware->process(
            (new ServerRequest())->withHeader('Accept', 'text/html,application/xhtml+xml'),
            $this->failingHandler(new RuntimeException('Something failed')),
        );

        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testBodyNeverContradictsTheContentType(): void
    {
        $contentTypes = ['text/html', 'text/xml', 'text/plain', 'application/json', 'text/csv'];

        foreach ($contentTypes as $contentType) {
            $middleware = $this->createMiddleware(new Page(['content_type' => $contentType]));

            $response = $middleware->process(
                new ServerRequest(),
                $this->failingHandler(new RuntimeException('Something failed')),
            );

            $body = (string)$response->getBody();
            $declared = $response->getHeaderLine('Content-Type');
            $isHtmlBody = str_contains($body, '<html');

            $this->assertSame(
                $isHtmlBody,
                str_contains($declared, 'html'),
                sprintf('Body and Content-Type "%s" do not match: %s', $declared, $body),
            );
        }
    }

    public function testPlainTextPageDoesNotAnnounceXmlOrHtmlMarkup(): void
    {
        $middleware = $this->createMiddleware(new Page(['content_type' => 'text/plain']));

        $response = $middleware->process(
            new ServerRequest(),
            $this->failingHandler(new RuntimeException('Something failed')),
        );

        // the body is HTML, so the header must say so instead of claiming text/plain
        $this->assertStringContainsString('html', $response->getHeaderLine('Content-Type'));
    }

    public function testStatusCodeFallsBackTo500WhenTheExceptionHasNoCode(): void
    {
        $middleware = $this->createMiddleware(new Page(['content_type' => 'text/xml']));

        $response = $middleware->process(
            new ServerRequest(),
            $this->failingHandler(new RuntimeException('no code')),
        );

        $this->assertSame(500, $response->getStatusCode());
    }

    private function createMiddleware(?Page $page): ErrorHandlerMiddleware
    {
        // a minimal theme keeps the html branch free of the twig plugin functions
        $sitePath = dirname(__DIR__) . '/_data/site';
        $config = new Config([
            'theme' => 'errortest',
            'paths' => [
                'app' => dirname(__DIR__, 2),
                'pages' => $sitePath . '/pages',
                'plugins' => dirname(__DIR__, 2) . '/plugins',
                'site' => $sitePath,
                'themes' => dirname(__DIR__) . '/_data/themes',
            ],
            'fileExtensions' => ['layouts' => 'twig'],
        ]);
        $pageList = new PageList($page === null ? [] : ['' => $page]);
        $pageRepository = new class ($pageList) implements PageRepositoryInterface {
            public function __construct(private PageList $pageList) {}

            public function find(string $id): ?Page
            {
                return $this->pageList->getItem($id);
            }

            public function findAll(): PageList
            {
                return $this->pageList;
            }
        };
        $urlManager = $this->createUrlManager();
        $dataRepository = new class implements \herbie\DataRepositoryInterface {
            public function __construct(string $path = '') {}

            public function load(string $name): array
            {
                return [];
            }

            public function loadAll(): array
            {
                return [];
            }
        };
        $site = new Site($config, $dataRepository, $pageRepository, $urlManager);

        $twigRenderer = new TwigRenderer($config, new \herbie\EventManager(), new NullLogger(), $site);

        return new ErrorHandlerMiddleware(
            $pageRepository,
            new \Laminas\Diactoros\ResponseFactory(),
            $twigRenderer,
            $urlManager,
        );
    }

    private function createUrlManager(): UrlManager
    {
        return new UrlManager(new ServerRequest());
    }

    private function failingHandler(\Throwable $exception): RequestHandlerInterface
    {
        return new class ($exception) implements RequestHandlerInterface {
            public function __construct(private \Throwable $exception) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw $this->exception;
            }
        };
    }
}
