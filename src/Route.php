<?php

namespace herbie;

class Route
{
    private string $rawPath;
    private string $path;
    private string $route;

    public function __construct(string $rawPath)
    {
        $this->rawPath = $rawPath;
        $this->path = $this->cleanPath($this->rawPath);
        $this->route = $this->toRoute($this->path);
    }

    /**
     * @return string
     */
    public function getRawPath(): string
    {
        return $this->rawPath;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    private function cleanPath(string $path): string
    {
        $path = str_unleading_slash($path);
        $scriptName = 'index.php';
        if (strpos($path, $scriptName) === 0) {
            $path = substr($path, strlen($scriptName));
        }
        return str_leading_slash($path);
    }

    private function toRoute(string $cleanedPath): string
    {
        return str_unleading_slash(str_untrailing_slash($cleanedPath));
    }
}
