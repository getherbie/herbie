<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Url;

use Herbie\Http\Request;

/**
 * The URLGenerator creates URLs based on the given route.
 */
class UrlGenerator
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var bool
     */
    protected $niceUrls;

    /**
     * Constructor
     * @param Request $request The request object.
     * @param bool $niceUrls Whether to generate nice URLs.
     */
    public function __construct(Request $request, $niceUrls)
    {
        $this->request = $request;
        $this->niceUrls = $niceUrls;
    }

    /**
     * Generates a relative URL based on the given route.
     * @param string $route The URL route. This should be in the format of 'route/to/a/page'.
     * @return string The generated relative URL.
     */
    public function generate($route)
    {
        $route = ltrim($route, '/');
        if ($this->niceUrls) {
            $url = $this->request->getBasePath() . '/' . $route;
        } else {
            $url = $this->request->getScriptName() . '/' . $route;
        }
        return $this->filterUrl($url);
    }

    /**
     * Generates an absolute URL based on the given route.
     * @param string $route The URL route. This should be in the format of 'route/to/a/page'.
     * @return string The generated absolute URL.
     */
    public function generateAbsolute($route)
    {
        $baseurl = $this->request->getScheme() . '://' . $this->request->getHttpHost();
        return $baseurl . $this->generate($route);
    }

    /**
     * Filters a generated URL.
     * @param string $url The URL.
     * @return string The filtered URL.
     */
    protected function filterUrl($url)
    {
        $url = preg_replace('/\/index$/', '', $url);
        return empty($url) ? '/' : rtrim($url, '/');
    }
}
