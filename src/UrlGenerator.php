<?php

declare(strict_types=1);

namespace herbie;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The URLGenerator creates URLs based on the given route.
 */
final class UrlGenerator
{
    private ServerRequestInterface $request;

    private Environment $environment;

    private bool $niceUrls;

    public function __construct(Config $config, Environment $environment, ServerRequestInterface $request)
    {
        $this->environment = $environment;
        $this->niceUrls = $config->get('niceUrls');
        $this->request = $request;
    }

    /**
     * Generates a relative URL based on the given route.
     * @param string $route The URL route. This should be in the format of 'route/to/a/page'.
     * @return string The generated relative URL.
     */
    public function generate(string $route): string
    {
        $route = ltrim($route, '/');
        if ($this->niceUrls) {
            $url = $this->environment->getBasePath() . '/' . $route;
        } else {
            $url = $this->environment->getScriptName() . '/' . $route;
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
        $absUrl = $this->request->getUri()->withPath($path);
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
        $url = rtrim($url, '/');
        return empty($url) ? '/' : $url;
    }
}
