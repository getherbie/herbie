<?php

declare(strict_types=1);

namespace herbie;

use Closure;
use InvalidArgumentException;

/**
 * Data-oriented site view with eager public properties and lazy-loaded aggregate fields.
 */
final readonly class Site
{
    /** @var object{modified: ?string, created: ?string} */
    private object $lazy;

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $route_params
     * @param Closure(): string $modifiedResolver
     * @param Closure(): string $createdResolver
     */
    public function __construct(
        public string $time,
        public array $data,
        public PageList $page_list,
        public PageTree $page_tree,
        public PageTrail $page_trail,
        public Config $config,
        public string $language,
        public string $locale,
        public string $charset,
        public string $base_url,
        public string $route,
        public array $route_params,
        public string $theme,
        private Closure $modifiedResolver,
        private Closure $createdResolver,
    ) {
        $this->lazy = (object)[
            'modified' => null,
            'created' => null,
        ];
    }

    public static function fromServices(
        Config $config,
        DataRepositoryInterface $dataRepository,
        PageRepositoryInterface $pageRepository,
        UrlManager $urlManager,
    ): self {
        [$route, $routeParams] = $urlManager->parseRequest();
        $pageList = $pageRepository->findAll();

        $modifiedResolver = static function () use ($pageRepository): string {
            $lastModified = 0;
            foreach ($pageRepository->findAll() as $item) {
                $modified = time_from_string($item->getModified());
                if ($modified > $lastModified) {
                    $lastModified = $modified;
                }
            }
            return date_format('c', $lastModified);
        };

        $createdResolver = static function () use ($pageRepository): string {
            $lastCreated = 0;
            foreach ($pageRepository->findAll() as $item) {
                $created = time_from_string($item->getCreated());
                if ($created > $lastCreated) {
                    $lastCreated = $created;
                }
            }
            return date_format('c', $lastCreated);
        };

        return new self(
            time: date_format('c'),
            data: $dataRepository->loadAll(),
            page_list: $pageList,
            page_tree: $pageList->getPageTree(),
            page_trail: $pageList->getPageTrail($route),
            config: $config,
            language: $config->getAsString('language'),
            locale: $config->getAsString('locale'),
            charset: $config->getAsString('charset'),
            base_url: $urlManager->createUrl('/'),
            route: $route,
            route_params: $routeParams,
            theme: $config->getAsString('theme'),
            modifiedResolver: $modifiedResolver,
            createdResolver: $createdResolver,
        );
    }

    public function getTime(): string
    {
        return $this->time;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getPageList(): PageList
    {
        return $this->page_list;
    }

    public function getPageTree(): PageTree
    {
        return $this->page_tree;
    }

    public function getPageTrail(): PageTrail
    {
        return $this->page_trail;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function getBaseUrl(): string
    {
        return $this->base_url;
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

    public function getModified(): string
    {
        if ($this->lazy->modified === null) {
            $this->lazy->modified = ($this->modifiedResolver)();
        }
        return $this->lazy->modified;
    }

    public function getCreated(): string
    {
        if ($this->lazy->created === null) {
            $this->lazy->created = ($this->createdResolver)();
        }
        return $this->lazy->created;
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        return match ($name) {
            'modified' => $this->getModified(),
            'created' => $this->getCreated(),
            default => throw new InvalidArgumentException("Field {$name} does not exist."),
        };
    }

    public function __isset(string $name): bool
    {
        return match ($name) {
            'modified', 'created' => true,
            default => false,
        };
    }
}
