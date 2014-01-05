<?php

namespace Herbie\Url;

use Symfony\Component\HttpFoundation\Request;

/**
 * The URL generator.
 *
 * @author Thomas Breuss <thomas.breuss@zephir.ch>
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
            return $this->request->getBaseUrl() . '/' . $route;
        } else {
            return $this->request->getScriptName() . '/' . $route;
        }
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

}
