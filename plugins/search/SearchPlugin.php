<?php

declare(strict_types=1);

namespace herbie\plugins\search;

use herbie\AbstractPlugin;
use herbie\Config;
use herbie\EventManager;
use herbie\Page;
use herbie\PageRepositoryInterface;
use herbie\Plugin;
use herbie\TwigRenderer;
use herbie\UrlManager;
use herbie\events\RenderSegmentEvent;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Twig\TwigFunction;

#[Plugin('search')]
final class SearchPlugin extends AbstractPlugin
{
    private CacheInterface $cache;
    private Config $config;
    private EventManager $eventManager;
    private PageRepositoryInterface $pageRepository;
    private ServerRequestInterface $request;
    private TwigRenderer $twigRenderer;
    private UrlManager $urlManager;

    public function __construct(
        CacheInterface $cache,
        Config $config,
        EventManager $eventManager,
        PageRepositoryInterface $pageRepository,
        ServerRequestInterface $request,
        TwigRenderer $twigRenderer,
        UrlManager $urlManager,
    ) {
        $this->cache = $cache;
        $this->config = $config;
        $this->eventManager = $eventManager;
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
     * Render page segments to plain text (for search matching).
     * Uses an internal search cache that is invalidated when any page changes.
     *
     * @param string $cacheToken Global cache token (most recent modified timestamp).
     */
    private function loadPageData(Page $item, string $cacheToken): array
    {
        // Cache key is per-page, but depends on the global token.
        // If any page changes, the token changes, old entries are stale.
        $content = strip_tags($this->renderPageContent($item));

        // The internal search cache can be disabled via the plugin configuration.
        // It is keyed per page with a global token of the latest modified timestamp,
        // so entries are invalidated automatically when any page changes.
        if ($this->config->getAsBool('plugins.search.useCache', true)) {
            $cacheId = 'search_' . $item->getRoute() . '_' . $cacheToken;
            $cached = $this->cache->get($cacheId);
            if ($cached !== null) {
                return [$item->getTitle(), $cached];
            }
            $this->cache->set($cacheId, $content);
        }

        return [$item->getTitle(), $content];
    }

    /**
     * Render page segments to HTML via the RenderSegmentEvent,
     * so all registered listeners (format parsers, Twig) are applied.
     */
    private function renderPageContent(Page $page): string
    {
        $rendered = [];
        foreach ($page->getSegments() as $segmentId => $segment) {
            /** @var RenderSegmentEvent $event */
            $event = $this->eventManager->dispatch(
                new RenderSegmentEvent($page, $segment, $segmentId, $this->urlManager),
            );
            $rendered[$segmentId] = $event->getSegment();
        }
        return implode('', $rendered);
    }

    /**
     * @param string $query
     * @return array
     */
    private function search(string $query): array
    {
        if (empty($query)) {
            return [];
        }

        // Build a global cache token once: the most recent modified timestamp across all pages.
        // If any page changes, the token changes and old cache entries become stale.
        $cacheToken = '';
        foreach ($this->pageRepository->findAll() as $page) {
            $m = $page->getModified();
            if ($m > $cacheToken) {
                $cacheToken = $m;
            }
        }

        $i = 1;
        $max = 100;
        $results = [];

        foreach ($this->pageRepository->findAll() as $item) {
            if (($i > $max) || empty($item->getTitle()) || !empty($item->no_search)) {
                continue;
            }
            $data = $this->loadPageData($item, $cacheToken);
            if ($this->match($query, $data)) {
                $content = $data[1] ?? '';
                $results[] = [
                    'title' => $item->getTitle(),
                    'route' => $item->getRoute(),
                    'date' => $item->getDate(),
                    'snippet' => $this->buildSnippet($content, $query),
                ];
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

    /**
     * Build a text snippet around the query term, with <mark> highlighting.
     */
    private function buildSnippet(string $content, string $query): string
    {
        /** @var string|null $normalized */
        $normalized = preg_replace('/\s+/', ' ', strip_tags($content));
        if ($normalized === null) {
            $normalized = strip_tags($content);
        }
        $content = $normalized;

        $snippetLength = 200;
        $queryLower = strtolower($query);
        $contentLower = strtolower($content);

        $pos = stripos($contentLower, $queryLower);
        if ($pos === false) {
            return '';
        }

        $before = 50;
        $end = $pos + strlen($query) + $snippetLength;
        $start = $pos - $before;

        // Trim to content boundaries (sentence breaks)
        if ($start > 0) {
            $spacePos = strrpos(substr($content, 0, $start), ' ');
            if ($spacePos !== false) {
                $start = $spacePos + 1;
            }
        } elseif ($start < 0) {
            // Query term at/near the beginning — start from 0.
            $start = 0;
        }

        if ($end < strlen($content)) {
            $nextSpace = strpos($content, ' ', $end);
            if ($nextSpace !== false) {
                $end = $nextSpace;
            }
        }

        $snippet = substr($content, $start, $end - $start);
        $highlight = htmlspecialchars($query, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Remove URL query parameters before highlighting
        // so we don't highlight "?query=..." URLs
        /** @var string|null $cleaned */
        $cleaned = preg_replace('/\?[a-z0-9_=+&]+$/i', '', $snippet);
        if ($cleaned === null) {
            $cleaned = $snippet;
        }

        /** @var string|null $highlighted */
        $highlighted = preg_replace(
            '/(' . preg_quote($query, '/') . ')/i',
            "<mark>$highlight</mark>",
            $cleaned,
        );
        if ($highlighted === null) {
            $highlighted = $cleaned;
        }

        $prefix = $start > 0 ? '…' : '';
        $suffix = $end < strlen($content) ? '…' : '';

        return $prefix . $highlighted . $suffix;
    }

    private function getTemplatePath(string $name): string
    {
        return '@plugin/search/templates/' . $name;
    }
}
