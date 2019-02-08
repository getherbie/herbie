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

namespace Herbie;

class Environment
{
    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var string
     */
    private $pathInfo;

    /**
     * @var string
     */
    private $requestUri;

    /**
     * Get the current route.
     * @return string
     */
    public function getRoute(): string
    {
        $route = $this->getRawRoute();
        return $route[0];
    }

    /**
     * Get the parts of the current route.
     * @return array
     */
    public function getRouteParts(): array
    {
        $route = $this->getRoute();
        return empty($route) ? [] : explode('/', $route);
    }

    /**
     * Get all routes from root to current page as an index array.
     * @return array
     */
    public function getRouteLine(): array
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
    public function getAction(): string
    {
        $route = $this->getRawRoute();
        return $route[1];
    }

    /**
     * @return array
     */
    private function getRawRoute(): array
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

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        if (null === $this->basePath) {
            $this->basePath = $this->detectBasePath();
        }

        return $this->basePath;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        if (null === $this->baseUrl) {
            $this->baseUrl = $this->detectBaseUrl();
        }
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function getPathInfo(): string
    {
        if (null === $this->pathInfo) {
            $this->pathInfo = $this->preparePathInfo();
        }

        return $this->pathInfo;
    }

    /**
     * @return string
     */
    public function getRequestUri(): string
    {
        if (null === $this->requestUri) {
            $this->requestUri = $this->detectRequestUri();
        }

        return $this->requestUri;
    }

    /**
     * @return string
     */
    private function detectRequestUri(): string
    {
        $requestUri = null;
        // Check this first so IIS will catch.
        $httpXRewriteUrl = $this->getServer('HTTP_X_REWRITE_URL');
        if ($httpXRewriteUrl !== null) {
            $requestUri = $httpXRewriteUrl;
        }
        // Check for IIS 7.0 or later with ISAPI_Rewrite
        $httpXOriginalUrl = $this->getServer('HTTP_X_ORIGINAL_URL');
        if ($httpXOriginalUrl !== null) {
            $requestUri = $httpXOriginalUrl;
        }
        // IIS7 with URL Rewrite: make sure we get the unencoded url
        // (double slash problem).
        $iisUrlRewritten = $this->getServer('IIS_WasUrlRewritten');
        $unencodedUrl    = $this->getServer('UNENCODED_URL', '');
        if ('1' == $iisUrlRewritten && '' !== $unencodedUrl) {
            return $unencodedUrl;
        }
        // HTTP proxy requests setup request URI with scheme and host [and port]
        // + the URL path, only use URL path.
        if (!$httpXRewriteUrl) {
            $requestUri = $this->getServer('REQUEST_URI');
        }
        if ($requestUri !== null) {
            return preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
        }
        // IIS 5.0, PHP as CGI.
        $origPathInfo = $this->getServer('ORIG_PATH_INFO');
        if ($origPathInfo !== null) {
            $queryString = $this->getServer('QUERY_STRING', '');
            if ($queryString !== '') {
                $origPathInfo .= '?' . $queryString;
            }
            return $origPathInfo;
        }
        return '/';
    }

    /**
     * @return string
     */
    private function detectBaseUrl(): string
    {
        $filename       = $this->getServer('SCRIPT_FILENAME', '');
        $scriptName     = $this->getServer('SCRIPT_NAME');
        $phpSelf        = $this->getServer('PHP_SELF');
        $origScriptName = $this->getServer('ORIG_SCRIPT_NAME');
        if ($scriptName !== null && basename($scriptName) === $filename) {
            $baseUrl = $scriptName;
        } elseif ($phpSelf !== null && basename($phpSelf) === $filename) {
            $baseUrl = $phpSelf;
        } elseif ($origScriptName !== null && basename($origScriptName) === $filename) {
            // 1and1 shared hosting compatibility.
            $baseUrl = $origScriptName;
        } else {
            // Backtrack up the SCRIPT_FILENAME to find the portion
            // matching PHP_SELF.
            $baseUrl  = '/';
            $basename = basename($filename);
            if ($basename) {
                $path     = ($phpSelf ? trim($phpSelf, '/') : '');
                $basePos  = strpos($path, $basename) ?: 0;
                $baseUrl .= substr($path, 0, $basePos) . $basename;
            }
        }
        // If the baseUrl is empty, then simply return it.
        if (empty($baseUrl)) {
            return '';
        }
        // Does the base URL have anything in common with the request URI?
        $requestUri = $this->getRequestUri();
        // Full base URL matches.
        if (0 === strpos($requestUri, $baseUrl)) {
            return $baseUrl;
        }
        // Directory portion of base path matches.
        $baseDir = str_replace('\\', '/', dirname($baseUrl));
        if (0 === strpos($requestUri, $baseDir)) {
            return $baseDir;
        }
        $truncatedRequestUri = $requestUri;
        if (false !== ($pos = strpos($requestUri, '?'))) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }
        $basename = basename($baseUrl);
        // No match whatsoever
        if (empty($basename) || false === strpos($truncatedRequestUri, $basename)) {
            return '';
        }
        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of the base path. $pos !== 0 makes sure it is not matching a
        // value from PATH_INFO or QUERY_STRING.
        if (strlen($requestUri) >= strlen($baseUrl)
            && (false !== ($pos = strpos($requestUri, $baseUrl)) && $pos !== 0)
        ) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }
        return $baseUrl;
    }

    /**
     * @return string
     */
    private function detectBasePath(): string
    {
        $filename = basename($this->getServer('SCRIPT_FILENAME', ''));
        $baseUrl  = $this->getBaseUrl();
        // Empty base url detected
        if ($baseUrl === '') {
            return '';
        }
        // basename() matches the script filename; return the directory
        if (basename($baseUrl) === $filename) {
            return str_replace('\\', '/', dirname($baseUrl));
        }
        // Base path is identical to base URL
        return rtrim($baseUrl, '/');
    }

    /**
     * @return string
     */
    private function preparePathInfo(): string
    {
        $baseUrl = $this->getBaseUrl();

        if (null === ($requestUri = $this->getRequestUri())) {
            return '/';
        }

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
     * @return string
     */
    public function getScriptName(): string
    {
        return $this->getServer('SCRIPT_NAME', $this->getServer('ORIG_SCRIPT_NAME', ''));
    }

    /**
     * @param string $name
     * @param null $default
     * @return string|null
     */
    private function getServer(string $name, $default = null): ?string
    {
        return $_SERVER[$name] ?? $default;
    }
}
