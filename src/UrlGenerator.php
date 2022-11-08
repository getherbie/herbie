<?php

declare(strict_types=1);

namespace herbie;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The URLGenerator creates URLs based on the given route.
 */
final class UrlGenerator
{
    private ServerRequestInterface $serverRequest;
    private string $baseUrl;
    private string $scriptUrl;
    private bool $niceUrls;

    public function __construct(ServerRequestInterface $serverRequest, array $options)
    {
        $this->serverRequest = $serverRequest;
        $this->baseUrl = (string)($options['baseUrl'] ?? '');
        $this->niceUrls = (bool)($options['niceUrls'] ?? false);
        $this->scriptUrl = (string)($options['scriptUrl'] ?? '');
    }

    /**
     * Generates a relative URL based on the given route.
     * @param string $route The URL route. This should be in the format of 'route/to/a/page'.
     * @return string The generated relative URL.
     */
    public function generate(string $route): string
    {
        $route = str_unleading_slash($route);
        if ($this->niceUrls) {
            $url = $this->baseUrl . '/' . $route;
        } else {
            $url = $this->scriptUrl . '/' . $route;
        }
        return $this->filterUrl($url);
    }

    /**
     * Generates an absolute URL based on the given route.
     * @param string $route The URL route. This should be in the format of 'route/to/a/page'.
     * @return string The generated absolute URL.
     */
    public function generateAbsolute(string $route): string
    {
        $path = $this->generate($route);
        $absUrl = $this->serverRequest->getUri()->withPath($path);
        return strval($absUrl);
    }

    /**
     * Filters a generated URL.
     * @param string $url The URL.
     * @return string The filtered URL.
     */
    private function filterUrl(string $url): string
    {
        $url = preg_replace('/\/index$/', '', $url);
        if (is_string($url)) {
            $url = str_untrailing_slash($url);
        }
        return empty($url) ? '/' : $url;
    }
}
