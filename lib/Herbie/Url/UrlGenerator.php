<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Url;

use Symfony\Component\HttpFoundation\Request;

/**
 * The URL generator.
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
     * @param Request $request
     * @param bool $niceUrls
     */
    public function __construct(Request $request, $niceUrls)
    {
        $this->request = $request;
        $this->niceUrls = $niceUrls;
    }

    /**
     * @param string $route
     * @return string
     */
    public function generate($route)
    {
        if ($this->niceUrls) {
            $url = $this->request->getBaseUrl() . '/' . $route;
        } else {
            $url = $this->request->getScriptName() . '/' . $route;
        }
        return $this->filterUrl($url);
    }

    /**
     * @param string $route
     * @return string
     */
    public function generateAbsolute($route)
    {
        $baseurl = $this->request->getScheme() . '://' . $this->request->getHttpHost();
        return $baseurl . $this->generate($route);
    }

    /**
     * @param string $url
     * @return string
     */
    protected function filterUrl($url)
    {
        $rpos = strrpos($url, '/index');
        if($rpos !== false) {
            $url = substr($url, 0, $rpos);
        }
        return empty($url) ? '/' : $url;
    }

}
