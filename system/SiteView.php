<?php

declare(strict_types=1);

namespace herbie;

/**
 * Immutable view for site-level template data.
 */
final readonly class SiteView
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $route_params
     */
    public function __construct(
        public string $time,
        public string $base_url,
        public string $charset,
        public Config $config,
        public string $created,
        public array $data,
        public string $language,
        public string $locale,
        public string $modified,
        public PageList $page_list,
        public PageTrail $page_trail,
        public PageTree $page_tree,
        public string $route,
        public array $route_params,
        public string $theme,
    ) {}

    public static function fromSite(Site $site): self
    {
        return new self(
            time: $site->time,
            base_url: $site->base_url,
            charset: $site->charset,
            config: $site->config,
            created: $site->getCreated(),
            data: $site->data,
            language: $site->language,
            locale: $site->locale,
            modified: $site->getModified(),
            page_list: $site->page_list,
            page_trail: $site->page_trail,
            page_tree: $site->page_tree,
            route: $site->route,
            route_params: $site->route_params,
            theme: $site->theme,
        );
    }

    public function getTime(): string
    {
        return $this->time;
    }

    public function getBaseUrl(): string
    {
        return $this->base_url;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getCreated(): string
    {
        return $this->created;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getModified(): string
    {
        return $this->modified;
    }

    public function getPageList(): PageList
    {
        return $this->page_list;
    }

    public function getPageTrail(): PageTrail
    {
        return $this->page_trail;
    }

    public function getPageTree(): PageTree
    {
        return $this->page_tree;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRouteParams(): array
    {
        return $this->route_params;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'time' => $this->time,
            'base_url' => $this->base_url,
            'charset' => $this->charset,
            'config' => $this->config,
            'created' => $this->created,
            'data' => $this->data,
            'language' => $this->language,
            'locale' => $this->locale,
            'modified' => $this->modified,
            'page_list' => $this->page_list,
            'page_trail' => $this->page_trail,
            'page_tree' => $this->page_tree,
            'route' => $this->route,
            'route_params' => $this->route_params,
            'theme' => $this->theme,
        ];
    }
}
