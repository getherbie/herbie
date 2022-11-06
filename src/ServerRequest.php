<?php

namespace herbie;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

class ServerRequest implements ServerRequestInterface, EnvironmentInterface
{
    private ServerRequestInterface $serverRequest;
    private UriInterface $serverRequestUri;
    private ?string $scriptFile;
    private ?string $scriptUrl;
    private ?string $baseUrl;

    public function __construct(ServerRequestInterface $serverRequest)
    {
        $this->serverRequest = $serverRequest;
        $this->serverRequestUri = $serverRequest->getUri();
        $this->scriptFile = null;
        $this->scriptUrl = null;
        $this->baseUrl = null;
    }

    public function getAuthority(): string
    {
        return $this->serverRequestUri->getAuthority();
    }

    public function getFragment(): string
    {
        return $this->serverRequestUri->getFragment(); // always empty on server?
    }

    public function getHost(): string
    {
        return $this->serverRequestUri->getHost();
    }

    public function getPath(): string
    {
        return $this->serverRequestUri->getPath();
    }

    /**
     * The path without leading script or base url.
     */
    public function getPathInfo(): string
    {
        $path = $this->getPath();
        $scriptUrl = $this->getScriptUrl();
        if (strpos($path, $scriptUrl) === 0) {
            return substr($path, strlen($scriptUrl));
        }
        $baseUrl = $this->getBaseUrl();
        if (strlen($baseUrl) === 0) {
            return $path;
        }
        if (strpos($path, $baseUrl) === 0) {
            return substr($path, strlen($baseUrl));
        }
        return $path;
    }

    public function getRoute(): string
    {
        return str_unleading_slash($this->getPathInfo());
    }

    public function getScriptFile(): string
    {
        if (isset($this->scriptFile)) {
            return $this->scriptFile;
        }

        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            return $_SERVER['SCRIPT_FILENAME'];
        }

        throw new RuntimeException('Unable to determine the entry script file path.');
    }

    public function getScriptUrl(): string
    {
        if ($this->scriptUrl === null) {
            $scriptFile = $this->getScriptFile();
            $scriptName = basename($scriptFile);
            if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                $this->scriptUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $scriptName) {
                $this->scriptUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                $this->scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
            } elseif (isset($_SERVER['PHP_SELF']) && ($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
                $this->scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
            } elseif (!empty($_SERVER['DOCUMENT_ROOT']) && strpos($scriptFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
                $this->scriptUrl = str_replace([$_SERVER['DOCUMENT_ROOT'], '\\'], ['', '/'], $scriptFile);
            } else {
                throw new RuntimeException('Unable to determine the entry script URL.');
            }
        }

        return $this->scriptUrl;
    }

    public function getBaseUrl(): string
    {
        if ($this->baseUrl === null) {
            $this->baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
        }

        return $this->baseUrl;
    }

    public function getPort(): string
    {
        return $this->serverRequestUri->getPort();
    }

    public function getQuery(): string
    {
        return $this->serverRequestUri->getQuery();
    }

    public function getScheme(): string
    {
        return $this->serverRequestUri->getScheme();
    }

    public function getUserInfo(): string
    {
        return $this->serverRequestUri->getUserInfo();
    }

    public function getProtocolVersion()
    {
        return $this->serverRequest->getProtocolVersion();
    }

    public function withProtocolVersion($version)
    {
        return $this->serverRequest->withProtocolVersion($version);
    }

    public function getHeaders()
    {
        return $this->serverRequest->getHeaders();
    }

    public function hasHeader($name)
    {
        return $this->serverRequest->hasHeader($name);
    }

    public function getHeader($name)
    {
        return $this->serverRequest->getHeader($name);
    }

    public function getHeaderLine($name)
    {
        return $this->serverRequest->getHeaderLine($name);
    }

    public function withHeader($name, $value)
    {
        return $this->serverRequest->withHeader($name, $value);
    }

    public function withAddedHeader($name, $value)
    {
        return $this->serverRequest->withAddedHeader($name, $value);
    }

    public function withoutHeader($name)
    {
        return $this->serverRequest->withoutHeader($name);
    }

    public function getBody()
    {
        return $this->serverRequest->getBody();
    }

    public function withBody(StreamInterface $body)
    {
        return $this->serverRequest->withBody($body);
    }

    public function getRequestTarget()
    {
        return $this->serverRequest->getRequestTarget();
    }

    public function withRequestTarget($requestTarget)
    {
        return $this->serverRequest->withRequestTarget($requestTarget);
    }

    public function getMethod()
    {
        return $this->serverRequest->getMethod();
    }

    public function withMethod($method)
    {
        return $this->serverRequest->withMethod($method);
    }

    public function getUri()
    {
        return $this->serverRequest->getUri();
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        return $this->serverRequest->withUri($uri, $preserveHost);
    }

    public function getServerParams()
    {
        return $this->serverRequest->getServerParams();
    }

    public function getCookieParams()
    {
        return $this->serverRequest->getCookieParams();
    }

    public function withCookieParams(array $cookies)
    {
        return $this->serverRequest->withCookieParams($cookies);
    }

    public function getQueryParams()
    {
        return $this->serverRequest->getQueryParams();
    }

    public function withQueryParams(array $query)
    {
        return $this->serverRequest->withQueryParams($query);
    }

    public function getUploadedFiles()
    {
        return $this->serverRequest->getUploadedFiles();
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        return $this->serverRequest->withUploadedFiles($uploadedFiles);
    }

    public function getParsedBody()
    {
        return $this->serverRequest->getParsedBody();
    }

    public function withParsedBody($data)
    {
        return $this->serverRequest->withParsedBody($data);
    }

    public function getAttributes()
    {
        return $this->serverRequest->getAttributes();
    }

    public function getAttribute($name, $default = null)
    {
        return $this->serverRequest->getAttribute($name, $default);
    }

    public function withAttribute($name, $value)
    {
        return $this->serverRequest->withAttribute($name, $value);
    }

    public function withoutAttribute($name)
    {
        return $this->serverRequest->withoutAttribute($name);
    }
}
