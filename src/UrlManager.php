<?php

declare(strict_types=1);

namespace herbie;

use Psr\Http\Message\ServerRequestInterface;

final class UrlManager
{
    private ServerRequestInterface $serverRequest;
    private string $baseUrl;
    private bool $niceUrls;
    private array $rules;
    private string $scriptUrl;
    /** @var array{string, array<string, string>}|null */
    private ?array $parsedRequest;

    public function __construct(ServerRequestInterface $serverRequest, array $options = [])
    {
        $this->serverRequest = $serverRequest;
        $this->baseUrl = (string)($options['baseUrl'] ?? '');
        $this->niceUrls = (bool)($options['niceUrls'] ?? false);
        $this->rules = (array)($options['rules'] ?? []);
        $this->scriptUrl = (string)($options['scriptUrl'] ?? '');
        $this->parsedRequest = null;
    }

    public function createUrl(string $route): string
    {
        // TODO add support for following routes
        // - "" -> currently requested route
        // - "images" -> relative route (doc/contents/images) if current route is "doc/contents"
        // - "/news/entry-one" -> absolute route -> /news/entry-one

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
        $path = $this->createUrl($route);
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
                throw new \UnexpectedValueException(sprintf('Invalid rule %s', $rule[0]));
            }
            $constraints = $rule[2] ?? [];
            $regex = $this->getRegex($rule[0], $constraints);
            if (!$regex) {
                continue;
            }
            if (preg_match($regex, $route, $matches)) {
                $params = array_intersect_key(
                    $matches,
                    array_flip(array_filter(array_keys($matches), 'is_string'))
                );
                return [$rule[1], $params];
            }
        }
        return $this->parsedRequest = [$route, []];
    }

    private function cleanPath(string $path): string
    {
        if (strlen($this->scriptUrl) > 0) {
            if (strpos($path, $this->scriptUrl) === 0) {
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
        $string = preg_replace_callback('/{([a-zA-Z0-9\_\-]+)}/', function ($matches) use ($replacements) {
            if (count($matches) === 2) {
                $name = $matches[1];
                if (empty($replacements[$name])) {
                    return "(?<" . $name . ">[a-zA-Z0-9\_\-]+)";
                }
                return "(?<" . $name . ">" . $replacements[$name] . ")";
            }
            return '';
        }, $pattern);

        if (is_null($string) || strlen($string) === 0) {
            return null;
        }

        // Add start and end matching
        return "@^" . $string . "$@D";
    }
}
