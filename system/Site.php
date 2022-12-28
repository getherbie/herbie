<?php

declare(strict_types=1);

namespace herbie;

use InvalidArgumentException;

/**
 * Stores the site.
 */
final class Site
{
    private Config $config;
    private DataRepositoryInterface $dataRepository;
    private PageRepositoryInterface $pageRepository;
    private UrlManager $urlManager;

    /**
     * Site constructor.
     */
    public function __construct(
        Config $config,
        DataRepositoryInterface $dataRepository,
        PageRepositoryInterface $pageRepository,
        UrlManager $urlManager
    ) {
        $this->config = $config;
        $this->dataRepository = $dataRepository;
        $this->pageRepository = $pageRepository;
        $this->urlManager = $urlManager;
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

    public function getPageTree(): PageTree
    {
        return $this->getPageList()->getPageTree();
    }

    public function getPageList(): PageList
    {
        return $this->pageRepository->findAll();
    }

    public function getPageTrail(): PageTrail
    {
        [$route] = $this->urlManager->parseRequest();
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

    public function getBaseUrl(): string
    {
        return $this->urlManager->createUrl('/');
    }

    public function getRoute(): string
    {
        return $this->urlManager->parseRequest()[0];
    }

    public function getRouteParams(): array
    {
        return $this->urlManager->parseRequest()[1];
    }

    public function getTheme(): string
    {
        return $this->config->getAsString('theme');
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        $getter = 'get' . str_replace('_', '', $name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } else {
            throw new InvalidArgumentException("Field {$name} does not exist.");
        }
    }

    public function __isset(string $name): bool
    {
        $getter = 'get' . str_replace('_', '', $name);
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else {
            return false;
        }
    }
}
