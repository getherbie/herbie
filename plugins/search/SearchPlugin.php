<?php

declare(strict_types=1);

namespace herbie\plugins\search;

use Exception;
use herbie\AbstractPlugin;
use herbie\Config;
use herbie\Page;
use herbie\PageRepositoryInterface;
use herbie\Plugin;
use herbie\TwigRenderer;
use herbie\UrlManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Twig\TwigFunction;

#[Plugin('search')]
final class SearchPlugin extends AbstractPlugin
{
    private CacheInterface $cache;
    private Config $config;
    private PageRepositoryInterface $pageRepository;
    private ServerRequestInterface $request;
    private TwigRenderer $twigRenderer;
    private UrlManager $urlManager;

    public function __construct(
        CacheInterface $cache,
        Config $config,
        PageRepositoryInterface $pageRepository,
        ServerRequestInterface $request,
        TwigRenderer $twigRenderer,
        UrlManager $urlManager,
    ) {
        $this->cache = $cache;
        $this->config = $config;
        $this->pageRepository = $pageRepository;
        $this->request = $request;
        $this->twigRenderer = $twigRenderer;
        $this->urlManager = $urlManager;
    }

    public function twigFunctions(): array
    {
        return [
            new TwigFunction('search_results', [$this, 'results'], ['is_safe' => ['html']]),
            new TwigFunction('search_form', [$this, 'form'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function form(): string
    {
        $name = $this->config->get('plugins.search.formTemplate', function () {
            return $this->getTemplatePath('form.twig');
        });

        [$route] = $this->urlManager->parseRequest();
        $queryParams = $this->request->getQueryParams();
        return $this->twigRenderer->renderTemplate($name, [
            'route' => $route,
            'query' => $queryParams['query'] ?? '',
        ]);
    }

    /**
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function results(): string
    {
        $queryParams = $this->request->getQueryParams();
        $query = $queryParams['query'] ?? '';
        $results = $this->search($query);

        $name = $this->config->get('plugins.search.resultsTemplate', function () {
            return $this->getTemplatePath('results.twig');
        });

        return $this->twigRenderer->renderTemplate($name, [
            'query' => $query,
            'results' => $results,
            'submitted' => true,
        ]);
    }

    /**
     * @param Page $item
     * @param bool $usePageCache
     * @return array
     */
    private function loadPageData(Page $item, bool $usePageCache): array
    {
        if (!$usePageCache) {
            $page = $this->pageRepository->find($item->getPath());
            if ($page === null) {
                return [$item->getTitle(), ''];
            }
            $title = $page->getTitle();
            $content = implode('', $page->getSegments());
            return [$title, $content];
        }

        $cacheId = $item->getCacheId();
        $html = $this->cache->get($cacheId);
        if ($html === null) {
            return [$item->getTitle(), ''];
        }
        return [$item->getTitle(), strip_tags($html)];
    }

    /**
     * @param string $query
     * @return array
     * @throws Exception
     */
    private function search(string $query): array
    {
        if (empty($query)) {
            return [];
        }

        $i = 1;
        $max = 100;
        $results = [];

        $usePageCache = $this->config->get('plugins.search.usePageCache', false);

        foreach ($this->pageRepository->findAll() as $item) {
            if (($i > $max) || empty($item->getTitle()) || !empty($item->no_search)) {
                continue;
            }
            $data = $this->loadPageData($item, $usePageCache);
            if ($this->match($query, $data)) {
                $results[] = $item;
                $i++;
            }
        }

        return $results;
    }

    /**
     * @param string $query
     * @param array $data
     * @return bool
     */
    private function match(string $query, array $data): bool
    {
        foreach ($data as $part) {
            if (empty($part)) {
                continue;
            }
            if (stripos($part, $query) !== false) {
                return true;
            }
        }
        return false;
    }

    private function getTemplatePath(string $name): string
    {
        return '@plugin/search/templates/' . $name;
    }
}
