<?php

namespace herbie;

use Psr\Http\Message\ServerRequestInterface;

class UrlManager
{
    private ServerRequestInterface $serverRequest;
    private string $baseUrl;
    private bool $niceUrls;
    private string $scriptUrl;

    public function __construct(ServerRequestInterface $serverRequest, array $options = [])
    {
        $this->serverRequest = $serverRequest;
        $this->baseUrl = (string)($options['baseUrl'] ?? '');
        $this->niceUrls = (bool)($options['niceUrls'] ?? false);
        $this->scriptUrl = (string)($options['scriptUrl'] ?? '');
    }

    public function createUrl(string $route)
    {
        $route = str_unleading_slash($route);
        if ($this->niceUrls) {
            $url = $this->baseUrl . '/' . $route;
        } else {
            $url = $this->scriptUrl . '/' . $route;
        }
        return $this->filterUrl($url);
    }

    public function createAbsoluteUrl(string $route): string
    {
        $path = $this->generate($route);
        $absUrl = $this->serverRequest->getUri()->withPath($path);
        return strval($absUrl);
    }

    public function parseRequest()
    {
        return [
            (string)'route',
            []
        ];
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
