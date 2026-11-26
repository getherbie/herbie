<?php

declare(strict_types=1);

namespace herbie\plugins\search;

use herbie\AbstractPlugin;
use herbie\Cache;
use herbie\Config;
use herbie\EventManager;
use herbie\events\RenderSegmentEvent;
use herbie\Page;
use herbie\PageRepositoryInterface;
use herbie\Plugin;
use herbie\TwigRenderer;
use herbie\UrlManager;
use Psr\Http\Message\ServerRequestInterface;
use Twig\TwigFunction;

#[Plugin('search')]
final class SearchPlugin extends AbstractPlugin
{
    private const int CACHE_TTL = 60 * 60 * 24;

    public function __construct(private readonly Cache $cache, private readonly Config $config, private readonly EventManager $eventManager, private readonly PageRepositoryInterface $pageRepository, private readonly ServerRequestInterface $request, private readonly TwigRenderer $twigRenderer, private readonly UrlManager $urlManager) {}

    #[\Override]
    public function twigFunctions(): array
    {
        return [
            new TwigFunction('search_results', $this->results(...), ['is_safe' => ['html']]),
            new TwigFunction('search_form', $this->form(...), ['is_safe' => ['html']]),
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
        $name = $this->config->get('plugins.search.formTemplate', fn(): string => $this->getTemplatePath('form.twig'));

        [$route] = $this->urlManager->parseRequest();
        $queryParams = $this->request->getQueryParams();
        return $this->twigRenderer->renderTemplate($name, [
            'route' => $route,
            'query' => $queryParams['query'] ?? '',
            'exact' => $this->isExactSearch($queryParams),
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
        $exact = $this->isExactSearch($queryParams);
        $results = $this->search($query, $exact);

        $name = $this->config->get('plugins.search.resultsTemplate', fn(): string => $this->getTemplatePath('results.twig'));

        return $this->twigRenderer->renderTemplate($name, [
            'query' => $query,
            'results' => $results,
            'submitted' => true,
            'exact' => $exact,
        ]);
    }

    /**
     * Render page segments to plain text (for search matching).
     * Uses an internal search cache that is invalidated when any page changes.
     *
     * @param int|string $cacheToken Global cache token (most recent modified timestamp).
     */
    private function loadPageData(Page $item, int|string $cacheToken): array
    {
        $content = $this->cache->remember(
            'search_' . md5($item->getRoute()),
            $cacheToken,
            fn(): string => $this->extractSearchText($item),
            self::CACHE_TTL,
            enabled: $this->config->getAsBool('plugins.search.useCache', true),
        );

        return [$item->getTitle(), $content];
    }

    private function extractSearchText(Page $item): string
    {
        $rendered = $this->renderPageContent($item);
        // Drop script/style elements entirely (including their content) so
        // hidden code/CSS is not indexed or shown in snippets.
        $noScripts = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $rendered);
        if ($noScripts !== null) {
            $rendered = $noScripts;
        }
        $noStyles = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $rendered);
        if ($noStyles !== null) {
            $rendered = $noStyles;
        }
        $withSpaces = preg_replace('/<[^>]+>/u', ' ', $rendered);
        $withSpaces ??= $rendered;
        return strip_tags($withSpaces);
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
    private function search(string $query, bool $exact): array
    {
        $terms = $this->extractSearchTerms($query);
        if ($terms === []) {
            return [];
        }

        // Use the page files' mtime published by the persistence, not the (cached) page list.
        $cacheToken = $this->cache->get('herbie_pages_modified');
        if (!is_int($cacheToken)) {
            clearstatcache(true);
            $cacheToken = 0;
            foreach ($this->pageRepository->findAll() as $page) {
                $path = $page->getPath();
                if (is_file($path)) {
                    $cacheToken = max($cacheToken, filemtime($path) ?: 0);
                }
            }
        }

        $i = 1;
        $max = 100;
        $results = [];

        foreach ($this->pageRepository->findAll() as $item) {
            if (($i > $max) || empty($item->getTitle()) || !empty($item->no_search) || $this->isSearchPage($item)) {
                continue;
            }
            $data = $this->loadPageData($item, $cacheToken);
            if ($this->match($query, $terms, $data, $exact)) {
                $content = $data[1] ?? '';
                $results[] = [
                    'title' => $item->getTitle(),
                    'route' => $item->getRoute(),
                    'date' => $item->getDate(),
                    'snippet' => $this->buildSnippet($content, $query, $terms, $exact),
                ];
                $i++;
            }
        }

        return $results;
    }

    /**
     * @param string $query
     * @param string[] $terms
     * @param array $data
     * @return bool
     */
    private function match(string $query, array $terms, array $data, bool $exact): bool
    {
        if ($exact) {
            // Exact search: the full query must appear as a contiguous phrase.
            foreach ($data as $part) {
                if (!empty($part) && mb_stripos($part, $query, 0, 'UTF-8') !== false) {
                    return true;
                }
            }
            return false;
        }

        // Default: every search term must appear (AND).
        foreach ($terms as $term) {
            $found = false;
            foreach ($data as $part) {
                if (empty($part)) {
                    continue;
                }
                if (mb_stripos($part, $term, 0, 'UTF-8') !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return string[]
     */
    private function extractSearchTerms(string $query): array
    {
        $terms = preg_split('/\s+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY);
        return $terms === false ? [] : $terms;
    }

    /**
     * Detect whether a page is (or contains) a search page.
     *
     * Such pages must never be indexed, otherwise rendering their content
     * (which calls search_results()) would trigger infinite recursion.
     *
     * @param Page $item
     */
    private function isSearchPage(Page $item): bool
    {
        // Defense in depth: skip any page that embeds the search functions.
        foreach ($item->getSegments() as $segment) {
            if (stripos($segment, 'search_results') !== false || stripos($segment, 'search_form') !== false) {
                return true;
            }
        }

        // Also skip the page currently being rendered (the search page).
        [$route] = $this->urlManager->parseRequest();
        if ($route !== '' && $item->getRoute() === $route) {
            return true;
        }

        return false;
    }

    /**
     * Resolve whether exact (whole-word) search is enabled:
     * a request-level "exact" parameter overrides the plugin configuration.
     *
     * @param array $queryParams
     */
    private function isExactSearch(array $queryParams): bool
    {
        if (isset($queryParams['exact'])) {
            return filter_var($queryParams['exact'], FILTER_VALIDATE_BOOLEAN);
        }
        return false;
    }

    /**
     * Build a text snippet around the (first) search term, with <mark> highlighting.
     *
     * In exact mode the full query is treated as a single phrase for anchoring
     * and highlighting; otherwise all terms are highlighted individually.
     *
     * @param string[] $terms
     */
    private function buildSnippet(string $content, string $query, array $terms, bool $exact): string
    {
        /** @var string|null $withSpaces */
        $withSpaces = preg_replace('/<[^>]+>/u', ' ', $content);
        $withSpaces ??= $content;
        /** @var string|null $normalized */
        $normalized = preg_replace('/\s+/u', ' ', strip_tags($withSpaces));
        $normalized ??= strip_tags($content);
        $content = $normalized;

        $snippetLength = 200;
        $anchor = $exact ? $query : ($terms[0] ?? $query);
        $contentLower = mb_strtolower($content, 'UTF-8');

        $pos = mb_stripos($content, $anchor, 0, 'UTF-8');
        if ($pos === false) {
            return '';
        }

        $before = 50;
        $end = $pos + mb_strlen($anchor, 'UTF-8') + $snippetLength;
        $start = $pos - $before;

        // Trim to content boundaries (sentence breaks)
        if ($start > 0) {
            $spacePos = mb_strrpos(mb_substr($content, 0, $start, 'UTF-8'), ' ', 0, 'UTF-8');
            if ($spacePos !== false) {
                $start = $spacePos + 1;
            }
        } elseif ($start < 0) {
            // Query term at/near the beginning — start from 0.
            $start = 0;
        }

        if ($end < mb_strlen($content, 'UTF-8')) {
            $nextSpace = mb_strpos($content, ' ', $end, 'UTF-8');
            if ($nextSpace !== false) {
                $end = $nextSpace;
            }
        }

        $snippet = mb_substr($content, $start, $end - $start, 'UTF-8');

        // Remove URL query parameters before highlighting
        // so we don't highlight "?query=..." URLs
        /** @var string|null $cleaned */
        $cleaned = preg_replace('/\?[a-z0-9_=+&]+$/iu', '', $snippet);
        $cleaned ??= $snippet;

        // Highlight search terms (exact mode: whole phrase; otherwise each term).
        if ($exact) {
            /** @var string|null $highlightedResult */
            $highlightedResult = preg_replace(
                '/(' . preg_quote($query, '/') . ')/iu',
                '<mark>' . htmlspecialchars($query, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</mark>',
                $cleaned,
            );
            $highlighted = $highlightedResult ?? $cleaned;
        } else {
            $highlighted = $cleaned;
            foreach ($terms as $term) {
                $result = preg_replace(
                    '/(' . preg_quote($term, '/') . ')/iu',
                    '<mark>' . htmlspecialchars($term, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</mark>',
                    $highlighted,
                );
                if ($result !== null) {
                    $highlighted = $result;
                }
            }
        }

        $prefix = $start > 0 ? '…' : '';
        $suffix = $end < mb_strlen($content, 'UTF-8') ? '…' : '';

        return $prefix . $highlighted . $suffix;
    }

    private function getTemplatePath(string $name): string
    {
        return '@plugin/search/templates/' . $name;
    }
}
