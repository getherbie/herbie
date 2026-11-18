<?php

declare(strict_types=1);

namespace herbie;

use ArrayAccess;
use BadMethodCallException;
use InvalidArgumentException;
use ReturnTypeWillChange;

/**
 * Immutable view for page template data.
 *
 * @implements ArrayAccess<string, mixed>
 */
final readonly class PageView implements ArrayAccess
{
    /**
     * @param string[] $authors
     * @param string[] $categories
     * @param string[] $tags
     * @param array{0: string, 1: int}|array{} $redirect
     * @param array<string, mixed> $custom
     */
    public function __construct(
        public string $id,
        public string $parent_id,
        public string $route,
        public string $parent_route,
        public string $title,
        public string $menu_title,
        public string $date,
        public string $created,
        public string $modified,
        public string $layout,
        public string $type,
        public string $path,
        public string $content_type,
        public string $format,
        public string $excerpt,
        public string $cache_id,
        public bool $hidden,
        public bool $cached,
        public bool $keep_extension,
        public bool $twig,
        public array $redirect,
        public array $authors,
        public array $categories,
        public array $tags,
        public ContentView $segments,
        public array $custom = [],
    ) {}

    public static function fromPage(Page $page): self
    {
        return new self(
            id: $page->getId(),
            parent_id: $page->getParentId(),
            route: $page->getRoute(),
            parent_route: $page->getParentRoute(),
            title: $page->getTitle(),
            menu_title: $page->getMenuTitle(),
            date: $page->getDate(),
            created: $page->getCreated(),
            modified: $page->getModified(),
            layout: $page->getLayout(),
            type: $page->getType(),
            path: $page->getPath(),
            content_type: $page->getContentType(),
            format: $page->getFormat(),
            excerpt: $page->getExcerpt(),
            cache_id: $page->getCacheId(),
            hidden: $page->getHidden(),
            cached: $page->getCached(),
            keep_extension: $page->getKeepExtension(),
            twig: $page->getTwig(),
            redirect: $page->getRedirect(),
            authors: $page->getAuthors(),
            categories: $page->getCategories(),
            tags: $page->getTags(),
            segments: ContentView::fromArray($page->getSegments()),
            custom: self::extractCustomData($page),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function extractCustomData(Page $page): array
    {
        $known = [
            'authors', 'cached', 'categories', 'content_type', 'created', 'date',
            'excerpt', 'format', 'hidden', 'id', 'keep_extension', 'layout',
            'menu_title', 'modified', 'parent_id', 'parent_route', 'path',
            'redirect', 'route', 'segments', 'tags', 'title', 'twig', 'type',
        ];

        $data = $page->toArray();
        foreach ($known as $key) {
            unset($data[$key]);
        }
        // toArray() may include virtual/computed keys depending on implementation; keep remaining as custom
        unset($data['cache_id'], $data['cacheId']);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge([
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'route' => $this->route,
            'parent_route' => $this->parent_route,
            'title' => $this->title,
            'menu_title' => $this->menu_title,
            'date' => $this->date,
            'created' => $this->created,
            'modified' => $this->modified,
            'layout' => $this->layout,
            'type' => $this->type,
            'path' => $this->path,
            'content_type' => $this->content_type,
            'format' => $this->format,
            'excerpt' => $this->excerpt,
            'cache_id' => $this->cache_id,
            'hidden' => $this->hidden,
            'cached' => $this->cached,
            'keep_extension' => $this->keep_extension,
            'twig' => $this->twig,
            'redirect' => $this->redirect,
            'authors' => $this->authors,
            'categories' => $this->categories,
            'tags' => $this->tags,
            'segments' => $this->segments->all(),
        ], $this->custom);
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            /** @var mixed $value */
            $value = $this->$name;
            return $value;
        }
        if (array_key_exists($name, $this->custom)) {
            return $this->custom[$name];
        }
        throw new InvalidArgumentException("Field {$name} does not exist.");
    }

    public function __isset(string $name): bool
    {
        if (property_exists($this, $name)) {
            return $this->$name !== null;
        }
        return array_key_exists($name, $this->custom) && $this->custom[$name] !== null;
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && $this->__isset($offset);
    }

    /**
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $offset)
    {
        if (!is_string($offset)) {
            throw new InvalidArgumentException('Offset must be of type string.');
        }
        return $this->__get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('PageView is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('PageView is immutable.');
    }
}
