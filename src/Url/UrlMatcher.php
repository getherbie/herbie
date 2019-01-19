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
use Herbie\Menu\MenuList;

/**
 * The URLMatcher matches a given route and returns the path to a valid page file.
 */
class UrlMatcher
{
    /**
     * @var MenuList List of all pages.
     */
    private $menuList;
    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor
     * @param MenuList $menuList List of all pages
     */
    public function __construct(MenuList $menuList, Config $config)
    {
        $this->menuList = $menuList;
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
        $item = $this->menuList->getItem($route);
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
            $item = $this->menuList->getItem($matchedRoute['route']);
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
     */
    private function matchRules(string $route): ?array
    {
        $matchedRoute = null;
        foreach ($this->config->rules as $ruleRegex => $ruleRoute) {
            $regex = $this->getRegex($ruleRegex);
            if (!$regex) {
                continue;
            }
            if (preg_match($regex, $route, $matches)) {
                $params = array_intersect_key(
                    $matches,
                    array_flip(array_filter(array_keys($matches), 'is_string'))
                );
                $matchedRoute = [
                    'rule' => $ruleRegex,
                    'route' => $ruleRoute,
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
     */
    private function getRegex($pattern): ?string
    {
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
