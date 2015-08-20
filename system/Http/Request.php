<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Http;

class Request
{
    /**
     * @var array
     */
    private $get;

    /**
     * @var array
     */
    private $post;

    /**
     * @var array
     */
    private $cookie;

    /**
     * @var array
     */
    private $server;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var
     */
    private $pathInfo;

    /**
     * @var string
     */
    private $requestUri;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->cookie = $_COOKIE;
        $this->files = $_FILES;
        $this->server = $_SERVER;
    }

    public function getQuery($name, $default = null)
    {
        return isset($this->get[$name]) ? $this->get[$name] : $default;
    }

    public function getPost($name, $default = null)
    {
        return isset($this->post[$name]) ? $this->post[$name] : $default;
    }

    public function getCookie($name, $default = null)
    {
        return isset($this->cookie[$name]) ? $this->cookie[$name] : $default;
    }

    public function getServer($name, $default = null)
    {
        return isset($this->server[$name]) ? $this->server[$name] : $default;
    }

    public function getHeader($name)
    {
        $name = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }
        return null;
    }

    public function getAuthData()
    {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            return null;
        }
        return ['user' => $_SERVER['PHP_AUTH_USER'], 'password' => $_SERVER['PHP_AUTH_PW']];
    }

    public function getRemoteAddress()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    public function getScheme()
    {
        // see https://github.com/zendframework/zend-http/blob/master/src/PhpEnvironment/Request.php
        if ((!empty($this->server['HTTPS']) && strtolower($this->server['HTTPS']) !== 'off')
            || (!empty($this->server['HTTP_X_FORWARDED_PROTO'])
                && $this->server['HTTP_X_FORWARDED_PROTO'] == 'https')
        ) {
            return 'https';
        } else {
            return 'http';
        }
    }

    public function getHttpHost()
    {
        return $this->getServer('HTTP_HOST');
    }

    public function getPort()
    {
        return 'https' === $this->getScheme() ? 443 : 80;
    }

    /**
     * Get the current route.
     * @return string
     */
    public function getRoute()
    {
        $route = $this->getRawRoute();
        return $route[0];
    }

    /**
     * Get the parts of the current route.
     * @return array
     */
    public function getRouteParts()
    {
        $route = $this->getRoute();
        return empty($route) ? [] : explode('/', $route);
    }

    /**
     * Get all routes from root to current page as an index array.
     * @return array
     */
    public function getRouteLine()
    {
        $route = '';
        $delim = '';
        $routeLine[] = ''; // root
        foreach ($this->getRouteParts() as $part) {
            $route .= $delim . $part;
            $routeLine[] = $route;
            $delim = '/';
        }
        return $routeLine;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        $route = $this->getRawRoute();
        return $route[1];
    }

    /**
     * @return array
     */
    private function getRawRoute()
    {
        $pathInfo = trim($this->getPathInfo(), '/');
        $pos = strrpos($pathInfo, ':');
        if ($pos !== false) {
            $parts = [substr($pathInfo, 0, $pos), substr($pathInfo, $pos + 1)];
        } else {
            $parts = [$pathInfo, ''];
        }
        return array_map('trim', $parts);
    }

    public function getBasePath()
    {
        if (null === $this->basePath) {
            $this->basePath = $this->prepareBasePath();
        }

        return $this->basePath;
    }

    public function getBaseUrl()
    {
        if (null === $this->baseUrl) {
            $this->baseUrl = $this->prepareBaseUrl();
        }
        return $this->baseUrl;
    }
    public function getPathInfo()
    {
        if (null === $this->pathInfo) {
            $this->pathInfo = $this->preparePathInfo();
        }

        return $this->pathInfo;
    }

    public function getRequestUri()
    {
        if (null === $this->requestUri) {
            $this->requestUri = $this->getServer('REQUEST_URI');
        }

        return $this->requestUri;
    }

    protected function prepareBaseUrl()
    {
        $filename = basename($this->getServer('SCRIPT_FILENAME'));

        if (basename($this->getServer('SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->getServer('SCRIPT_NAME');
        } elseif (basename($this->getServer('PHP_SELF')) === $filename) {
            $baseUrl = $this->getServer('PHP_SELF');
        } elseif (basename($this->getServer('ORIG_SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->getServer('ORIG_SCRIPT_NAME'); // 1and1 shared hosting compatibility
        } else {
            // Backtrack up the script_filename to find the portion matching
            // php_self
            $path = $this->getServer('PHP_SELF', '');
            $file = $this->getServer('SCRIPT_FILENAME', '');
            $segs = explode('/', trim($file, '/'));
            $segs = array_reverse($segs);
            $index = 0;
            $last = count($segs);
            $baseUrl = '';
            do {
                $seg = $segs[$index];
                $baseUrl = '/'.$seg.$baseUrl;
                ++$index;
            } while ($last > $index && (false !== $pos = strpos($path, $baseUrl)) && 0 != $pos);
        }

        // Does the baseUrl have anything in common with the request_uri?
        $requestUri = $this->getRequestUri();

        if ($baseUrl && false !== $prefix = $this->getUrlencodedPrefix($requestUri, $baseUrl)) {
            // full $baseUrl matches
            return $prefix;
        }

        if ($baseUrl && false !== $prefix = $this->getUrlencodedPrefix($requestUri, rtrim(dirname($baseUrl), '/').'/')) {
            // directory portion of $baseUrl matches
            return rtrim($prefix, '/');
        }

        $truncatedRequestUri = $requestUri;
        if (false !== $pos = strpos($requestUri, '?')) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }

        $basename = basename($baseUrl);
        if (empty($basename) || !strpos(rawurldecode($truncatedRequestUri), $basename)) {
            // no match whatsoever; set it blank
            return '';
        }

        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of baseUrl. $pos !== 0 makes sure it is not matching a value
        // from PATH_INFO or QUERY_STRING
        if (strlen($requestUri) >= strlen($baseUrl) && (false !== $pos = strpos($requestUri, $baseUrl)) && $pos !== 0) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return rtrim($baseUrl, '/');
    }

    /**
     * Prepares the base path.
     *
     * @return string base path
     */
    protected function prepareBasePath()
    {
        $filename = basename($this->getServer('SCRIPT_FILENAME'));
        $baseUrl = $this->getBaseUrl();
        if (empty($baseUrl)) {
            return '';
        }

        if (basename($baseUrl) === $filename) {
            $basePath = dirname($baseUrl);
        } else {
            $basePath = $baseUrl;
        }

        if ('\\' === DIRECTORY_SEPARATOR) {
            $basePath = str_replace('\\', '/', $basePath);
        }

        return rtrim($basePath, '/');
    }

    protected function preparePathInfo()
    {
        $baseUrl = $this->getBaseUrl();

        if (null === ($requestUri = $this->getRequestUri())) {
            return '/';
        }

        $pathInfo = '/';

        // Remove the query string from REQUEST_URI
        if ($pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        $pathInfo = substr($requestUri, strlen($baseUrl));
        if (null !== $baseUrl && (false === $pathInfo || '' === $pathInfo)) {
            // If substr() returns false then PATH_INFO is set to an empty string
            return '/';
        } elseif (null === $baseUrl) {
            return $requestUri;
        }

        return (string) $pathInfo;
    }

    /**
     * Returns current script name.
     *
     * @return string
     *
     * @api
     */
    public function getScriptName()
    {
        return $this->getServer('SCRIPT_NAME', $this->getServer('ORIG_SCRIPT_NAME', ''));
    }

    private function getUrlencodedPrefix($string, $prefix)
    {
        if (0 !== strpos(rawurldecode($string), $prefix)) {
            return false;
        }

        $len = strlen($prefix);

        if (preg_match(sprintf('#^(%%[[:xdigit:]]{2}|.){%d}#', $len), $string, $match)) {
            return $match[0];
        }

        return false;
    }

}

