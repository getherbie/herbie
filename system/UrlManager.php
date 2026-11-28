<?php

declare(strict_types=1);

namespace herbie;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use UnexpectedValueException;

final class UrlManager
{
    private readonly string $baseUrl;
    private readonly string $absoluteBaseUrl;
    private readonly bool $niceUrls;
    private readonly array $rules;
    private readonly string $scriptUrl;
    /** @var array{string, array<string, string>}|null */
    private ?array $parsedRequest;

    public function __construct(private readonly ServerRequestInterface $serverRequest, array $options = [])
    {
        $this->baseUrl = (string)($options['baseUrl'] ?? '');
        $this->absoluteBaseUrl = $this->normalizeOrigin((string)($options['absoluteBaseUrl'] ?? ''));
        $this->niceUrls = (bool)($options['niceUrls'] ?? false);
        $this->rules = (array)($options['rules'] ?? []);
        $this->scriptUrl = (string)($options['scriptUrl'] ?? '');
        $this->parsedRequest = null;
    }

    public function createAbsoluteUrl(string $route): string
    {
        return $this->absoluteUrl($this->createUrl($route));
    }

    public function createAbsoluteWebUrl(string $path): string
    {
        return $this->absoluteUrl($this->createWebUrl($path));
    }

    /**
     * Adds the configured origin to a path, or falls back to the current request.
     */
    private function absoluteUrl(string $path): string
    {
        if ($this->absoluteBaseUrl !== '') {
            return rtrim($this->absoluteBaseUrl, '/') . '/' . ltrim($path, '/');
        }
        return (string)$this->serverRequest->getUri()->withPath($path);
    }

    private function normalizeOrigin(string $origin): string
    {
        if ($origin === '') {
            return '';
        }

        $parts = parse_url($origin);
        if ($parts === false
            || !isset($parts['scheme'], $parts['host'])
            || $parts['host'] === ''
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || (isset($parts['path']) && trim($parts['path'], '/') !== '')
        ) {
            throw new InvalidArgumentException(
                'urls.origin must be a scheme and host, e.g. https://example.com.',
            );
        }

        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        return strtolower($parts['scheme']) . '://' . strtolower($parts['host']) . $port;
    }

    /**
     * Creates a URL for a route.
     *
     * The route is always interpreted relative to the site root, a leading slash makes no
     * difference. An empty route yields "/" and is not resolved to the current route; callers
     * must pass a route.
     */
    public function createUrl(string $route): string
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
     * Creates a URL for a file below the web root.
     *
     * Unlike createUrl() the result never contains the script name, because the web server
     * delivers the file directly.
     */
    public function createWebUrl(string $path): string
    {
        return $this->baseUrl . '/' . str_unleading_slash($path);
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
        if ($url === $this->scriptUrl) {
            $url = '/';
        }
        return empty($url) ? '/' : $url;
    }

    /**
     * @return array{string, array<string, string>}
     */
    public function parseRequest(): array
    {
        if (isset($this->parsedRequest)) {
            return $this->parsedRequest;
        }
        $path = $this->serverRequest->getUri()->getPath();
        $route = $this->cleanPath($path);
        foreach ($this->rules as $rule) {
            if (count($rule) < 2) {
                throw new UnexpectedValueException(sprintf('Invalid rule %s', $rule[0]));
            }
            $constraints = $rule[2] ?? [];
            $regex = $this->getRegex($rule[0], $constraints);
            if (!$regex) {
                continue;
            }
            if (preg_match($regex, $route, $matches)) {
                $params = array_intersect_key(
                    $matches,
                    array_flip(array_filter(array_keys($matches), is_string(...))),
                );
                return $this->parsedRequest = [$rule[1], $params];
            }
        }
        return $this->parsedRequest = [$route, []];
    }

    private function cleanPath(string $path): string
    {
        if ($this->scriptUrl !== '') {
            if (str_starts_with($path, $this->scriptUrl)) {
                $path = substr($path, strlen($this->scriptUrl));
            }
        }

        return str_unleading_slash($path);
    }

    /**
     * @param array<string, string> $replacements
     * @see https://stackoverflow.com/questions/30130913/how-to-do-url-matching-regex-for-routing-framework
     * @see https://laravel.com/docs/5.7/routing
     */
    private function getRegex(string $pattern, array $replacements): ?string
    {
        $string = preg_replace_callback('/{([a-zA-Z0-9\_\-]+)}/', function (array $matches) use ($replacements): string {
            if (count($matches) === 2) {
                $name = $matches[1];
                if (empty($replacements[$name])) {
                    return "(?<" . $name . ">[a-zA-Z0-9\_\-]+)";
                }
                return "(?<" . $name . ">" . $replacements[$name] . ")";
            }
            return '';
        }, $pattern);

        if ($string === null || $string === '') {
            return null;
        }

        // Add start and end matching
        return "@^" . $string . "$@D";
    }
}
