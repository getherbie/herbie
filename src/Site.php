<?php

declare(strict_types=1);

namespace herbie;

/**
 * Stores the site.
 */
final class Site
{
    private Config $config;
    private DataRepositoryInterface $dataRepository;
    private PageRepositoryInterface $pageRepository;
    private Route $route;

    /**
     * Site constructor.
     */
    public function __construct(
        Config $config,
        DataRepositoryInterface $dataRepository,
        PageRepositoryInterface $pageRepository,
        Route $Route
    ) {
        $this->config = $config;
        $this->dataRepository = $dataRepository;
        $this->pageRepository = $pageRepository;
        $this->route = $Route;
    }

    public function getTime(): string
    {
        return date_format('c');
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->dataRepository->loadAll();
    }

    public function getPageList(): PageList
    {
        return $this->pageRepository->findAll();
    }

    public function getPageTree(): PageTree
    {
        return $this->getPageList()->getPageTree();
    }

    public function getPageTrail(): PageTrail
    {
        $route = $this->route->getRoute();
        return $this->getPageList()->getPageTrail($route);
    }

    public function getModified(): string
    {
        $lastModified = 0;
        foreach ($this->pageRepository->findAll() as $item) {
            $modified = time_from_string($item->getModified());
            if ($modified > $lastModified) {
                $lastModified = $modified;
            }
        }
        return date_format('c', $lastModified);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getLanguage(): string
    {
        return $this->config->getAsString('language');
    }

    public function getLocale(): string
    {
        return $this->config->getAsString('locale');
    }

    public function getCharset(): string
    {
        return $this->config->getAsString('charset');
    }
}
