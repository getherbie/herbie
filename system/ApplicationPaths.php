<?php

declare(strict_types=1);

namespace herbie;

final class ApplicationPaths
{
    private string $app;
    private string $site;
    private string $vendor;
    private string $web;

    public function __construct(string $app, ?string $site = null, ?string $vendor = null, ?string $web = null)
    {
        $this->app = $app;
        $this->site = $site ?? ($app . '/site');
        $this->vendor = $vendor ?? ($app . '/vendor');
        $this->web = $web ?? ($app . '/web');
    }

    public function getApp(string $append = ''): string
    {
        return $this->app . $append;
    }

    public function getSite(string $append = ''): string
    {
        return $this->site . $append;
    }

    public function setSite(string $path): self
    {
        $this->site = $path;
        return $this;
    }

    public function getVendor(): string
    {
        return $this->vendor;
    }

    public function setVendor(string $path): self
    {
        $this->vendor = $path;
        return $this;
    }

    public function getWeb(): string
    {
        return $this->web;
    }

    public function setWeb(string $path): self
    {
        $this->web = $path;
        return $this;
    }
}
