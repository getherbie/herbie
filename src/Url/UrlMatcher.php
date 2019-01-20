<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie\Url;

use Herbie\Config;
use Herbie\Exception\HttpException;
use Herbie\Page\PageList;

/**
 * The URLMatcher matches a given route and returns the path to a valid page file.
 */
class UrlMatcher
{
    /**
     * @var PageList List of all pages.
     */
    private $pageList;
    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor
     * @param PageList $pageList List of all pages
     * @param Config $config
     */
    public function __construct(PageList $pageList, Config $config)
    {
        $this->pageList = $pageList;
        $this->config = $config;
    }

    /**
     * Returns a path to a valid page file.
     * @param string $route The route of the current request.
     * @return array
     * @throws HttpException
     */
    public function match(string $route): array
    {
        // match by normal route
        $item = $this->pageList->getItem($route);
        if (isset($item)) {
            return [
                'route' => $item->getRoute(),
                'path' => $item->getPath(),
                'params' => []
            ];
        }

        // match by url rules
        $matchedRoute = $this->matchRules($route);
        if ($matchedRoute) {
            $item = $this->pageList->getItem($matchedRoute['route']);
            if (isset($item)) {
                return [
                    'route' => $item->getRoute(),
                    'path' => $item->getPath(),
                    'params' => $matchedRoute['params']
                ];
            }
        }

        throw HttpException::notFound('Page "' . $route . '" not found');
    }

    /**
     * @param string $route
     * @return array|null
     * @throws \Exception
     */
    private function matchRules(string $route): ?array
    {
        $matchedRoute = null;
        foreach ($this->config->rules->toArray() as $rule) {
            if (count($rule) < 2) {
                throw new \Exception(sprintf('Invalid rule %s', $rule[0]));
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
                $matchedRoute = [
                    'rule' => $rule[0],
                    'route' => $rule[1],
                    'params' => $params
                ];
                break;
            }
        }
        return $matchedRoute;
    }

    /**
     * @param $pattern
     * @return string
     * @see https://stackoverflow.com/questions/30130913/how-to-do-url-matching-regex-for-routing-framework
     * @see https://laravel.com/docs/5.7/routing
     */
    private function getRegex($pattern, array $replacements): ?string
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

        // Add start and end matching
        $patternAsRegex = "@^" . $string . "$@D";

        return $patternAsRegex;


        if (preg_match('/[^-:\/_{}()a-zA-Z\d]/', $pattern)) {
            return null; // Invalid pattern
        }

        // Turn "(/)" into "/?"
        $pattern = preg_replace('#\(/\)#', '/?', $pattern);

        // Create capture group for ":parameter"
        $allowedParamChars = '[a-zA-Z0-9\_\-]+';
        $pattern = preg_replace(
            '/:(' . $allowedParamChars . ')/',   # Replace ":parameter"
            '(?<$1>' . $allowedParamChars . ')', # with "(?<parameter>[a-zA-Z0-9\_\-]+)"
            $pattern
        );

        // Create capture group for '{parameter}'
        $pattern = preg_replace(
            '/{(' . $allowedParamChars . ')}/',    # Replace "{parameter}"
            '(?<$1>' . $allowedParamChars . ')', # with "(?<parameter>[a-zA-Z0-9\_\-]+)"
            $pattern
        );

        // Add start and end matching
        $patternAsRegex = "@^" . $pattern . "$@D";

        return $patternAsRegex;
    }
}
