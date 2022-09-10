<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

/**
 * The URLMatcher matches a given route and returns the path to a valid page file.
 */
final class UrlMatcher
{
    private Config $config;

    private PageRepositoryInterface $pageRepository;

    public function __construct(Config $config, PageRepositoryInterface $pageRepository)
    {
        $this->config = $config;
        $this->pageRepository = $pageRepository;
    }

    /**
     * Returns a path to a valid page file.
     * @param string $route The route of the current request.
     * @return array
     */
    public function match(string $route): array
    {
        $pageList = $this->pageRepository->findAll();

        // match by normal route
        $item = $pageList->getItem($route);
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
            $item = $pageList->getItem($matchedRoute['route']);
            if (isset($item)) {
                return [
                    'route' => $item->getRoute(),
                    'path' => $item->getPath(),
                    'params' => $matchedRoute['params']
                ];
            }
        }

        return [];
    }

    private function matchRules(string $route): ?array
    {
        $matchedRoute = null;
        $rules = $this->config->getAsArray('rules');
        foreach ($rules as $rule) {
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
     * @param array $replacements
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

        if (is_null($string) || strlen($string) === 0) {
            return null;
        }
        
        // Add start and end matching
        return "@^" . $string . "$@D";
    }
}
