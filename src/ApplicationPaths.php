<?php

declare(strict_types=1);

namespace herbie;

final class ApplicationPaths
{
    private string $app;
    private string $site;
    private string $vendor;

    public function __construct(string $app, ?string $site = null, ?string $vendor = null)
    {
        $this->app = $app;
        $this->site = $site ?? ($app . '/site');
        $this->vendor = $vendor ?? ($app . '/vendor');
    }

    public function getApp(string $append = ''): string
    {
        return $this->app . $append;
    }

    public function getSite(string $append = ''): string
    {
        return $this->site . $append;
    }

    public function getVendor(): string
    {
        return $this->vendor;
    }
}
