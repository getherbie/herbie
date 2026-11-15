<?php

declare(strict_types=1);

namespace herbie;

use ArrayAccess;
use Ausi\SlugGenerator\SlugGenerator;
use BadMethodCallException;
use InvalidArgumentException;
use ReturnTypeWillChange;

/**
 * A page of the site.
 *
 * All attributes are public properties, so they can be read without a getter. The values,
 * that come from the page properties block, are normalized by the setData() method: A date
 * is converted to an ISO date, the format is mapped to its internal name and the redirect
 * is validated. Unknown properties are stored as custom data.
 *
 * @implements ArrayAccess<string, mixed>
 */
final class Page implements ArrayAccess
{
    /** @var string[] */
    public array $authors = [];
    public bool $cached = true;
    /** @var string[] */
    public array $categories = [];
    public string $content_type = 'text/html';
    public string $created = '';
    public string $date = '';
    public string $excerpt = '';
    public string $format = 'raw';
    public bool $hidden = false;
    public string $id = '';
    public bool $keep_extension = false;
    public string $layout = 'default';
    public string $menu_title = '';
    public string $modified = '';
    public string $parent_id = '';
    public string $parent_route = '';
    public string $path = '';
    /** @var array{string, int}|array{} */
    public array $redirect = [];
    public string $route = '';
    /** @var null|string[] */
    public ?array $segments = null;
    /** @var string[] */
    public array $tags = [];
    public string $title = '';
    public bool $twig = true;
    public string $type = 'page';

    /** @var array<int|string, mixed> */
    private array $customData = [];

    public function __construct(
        array $data = [],
        ?array $segments = null,
        private ?SlugGenerator $slugGenerator = null
    ) {
        $this->setData($data);
        if ($segments !== null) {
            $this->setSegments($segments);
        }
    }

    /**
     * Sets all attributes from the page properties. Dates are converted to an ISO date,
     * the format is mapped to its internal name, the redirect is validated and unknown
     * keys are stored as custom data.
     *
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        if (array_key_exists('data', $data)) {
            throw new InvalidArgumentException("Field data is not allowed.");
        }
        if (array_key_exists('segments', $data)) {
            throw new InvalidArgumentException("Field segments is not allowed.");
        }
        foreach ($data as $key => $value) {
            $this->__set((string)$key, $value);
        }
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getData(): array
    {
        return $this->customData;
    }

    public function getSegment(string $id): string
    {
        $segments = $this->getSegments();
        if (array_key_exists($id, $segments)) {
            return (string)$segments[$id];
        }
        return '';
    }

    /**
     * @return string[]
     */
    public function getSegments(): array
    {
        if ($this->segments === null) {
            $content = file_read($this->path);
            [, $this->segments] = FlatFilePagePersistence::parseFileContent($content);
        }
        return $this->segments ?? [];
    }

    /**
     * @param string[] $segments
     */
    public function setSegments(array $segments = []): void
    {
        $this->segments = $segments;
    }

    public function getCacheId(): string
    {
        return 'page-' . $this->id;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function getMenuTitle(): string
    {
        return $this->menu_title !== '' ? $this->menu_title : $this->title;
    }

    /**
     * @return array{string, int}|array{}
     */
    public function getRedirect(): array
    {
        return $this->redirect;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getParentId(): string
    {
        return $this->parent_id;
    }

    public function getParentRoute(): string
    {
        return $this->parent_route;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getCreated(): string
    {
        return $this->created;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getModified(): string
    {
        return $this->modified;
    }

    public function getTwig(): bool
    {
        return $this->twig;
    }

    public function getKeepExtension(): bool
    {
        return $this->keep_extension;
    }

    public function getContentType(): string
    {
        return $this->content_type;
    }

    public function getCached(): bool
    {
        return $this->cached;
    }

    public function getHidden(): bool
    {
        return $this->hidden;
    }

    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @return string[]
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * @return string[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getAuthor(string $author): string
    {
        return $this->findBySlug($this->authors, $author);
    }

    public function getCategory(string $category): string
    {
        return $this->findBySlug($this->categories, $category);
    }

    public function getTag(string $tag): string
    {
        return $this->findBySlug($this->tags, $tag);
    }

    public function hasAuthor(string $author): bool
    {
        return $this->findBySlug($this->authors, $author) !== '';
    }

    public function hasCategory(string $category): bool
    {
        return $this->findBySlug($this->categories, $category) !== '';
    }

    public function hasTag(string $tag): bool
    {
        return $this->findBySlug($this->tags, $tag) !== '';
    }

    public function isStartPage(): bool
    {
        return $this->route === '';
    }

    public function routeEquals(string $route): bool
    {
        return $this->route === $route;
    }

    public function routeInPageTrail(string $route): bool
    {
        if (empty($route) || empty($this->route)) {
            return false;
        }
        return 0 === strpos($route, $this->route);
    }

    public function isStaticPage(): bool
    {
        return 0 === strpos($this->path, '@page');
    }

    public function __toString(): string
    {
        return $this->title;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = get_object_vars($this);
        unset($array['slugGenerator'], $array['customData']);
        return array_merge($array, $this->customData);
    }

    /**
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        return $this->__isset((string)$offset);
    }

    public function __isset(string $name): bool
    {
        if (property_exists($this, $name)) {
            return $this->$name !== null;
        }
        return method_exists($this, 'get' . ucfirst($name)) || isset($this->customData[$name]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->__get((string)$offset);
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        // derived attributes without a property, e.g. "cacheId"
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        if (array_key_exists($name, $this->customData)) {
            return $this->customData[$name];
        }
        throw new InvalidArgumentException("Field {$name} does not exist.");
    }

    /**
     * Sets a page property from the page properties block. The value is normalized here,
     * because a direct property assignment can't be intercepted.
     *
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        if (property_exists($this, $name) && $name !== 'segments') {
            $this->{$name} = $this->normalize($name, $value);
            return;
        }
        $this->customData[$name] = $value;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->__set((string)$offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('Unset is not supported.');
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function normalize(string $name, $value)
    {
        if (in_array($name, ['created', 'date', 'modified'], true)) {
            return is_scalar($value) ? $this->formatDate($value) : '';
        }
        if ($name === 'format') {
            return self::toInternalFormat(is_string($value) ? $value : '');
        }
        if ($name === 'redirect') {
            return $this->parseRedirect($value);
        }
        return $value;
    }

    private static function toInternalFormat(string $format): string
    {
        return match ($format) {
            'md', 'markdown' => 'markdown',
            'textile' => 'textile',
            'rst' => 'rest',
            default => 'raw',
        };
    }

    /**
     * @param bool|float|int|string $date
     */
    private function formatDate($date): string
    {
        if (is_bool($date) || is_float($date)) {
            return '';
        }
        if (is_string($date)) {
            $date = trim($date);
        }
        if (is_natural($date, true)) {
            return date_format('c', (int)$date);
        }
        if (is_string($date)) {
            $time = time_from_string($date);
            if ($time > 0) {
                return date_format('c', $time);
            }
        }
        return '';
    }

    /**
     * @param mixed $redirect
     * @return array{string, int}
     */
    private function parseRedirect($redirect): array
    {
        if (!is_array($redirect) && !is_string($redirect)) {
            throw new InvalidArgumentException('Redirect must be a string or an array{string,int}.');
        }
        if (is_string($redirect)) {
            $redirect = trim($redirect);
            if ($redirect === '') {
                throw new InvalidArgumentException('Redirect must be a non-empty string.');
            }
            $redirect = [$redirect, 302];
        }
        if (count($redirect) !== 2) {
            throw new InvalidArgumentException('Redirect array must be an array{string,int}.');
        }
        if (!is_string($redirect[0]) || trim($redirect[0]) === '') {
            throw new InvalidArgumentException('Redirect array[0] must be a non-empty string.');
        }
        if (!is_natural($redirect[1])) {
            throw new InvalidArgumentException('Redirect array[1] must be a integer.');
        }
        if ($redirect[1] < 300 || $redirect[1] > 308) {
            throw new InvalidArgumentException('Redirect array[1] must be a status code between 300 and 308.');
        }
        return [trim($redirect[0]), (int)$redirect[1]];
    }

    /**
     * @param string[] $values
     */
    private function findBySlug(array $values, string $slug): string
    {
        if ($this->slugGenerator === null) {
            throw new BadMethodCallException('SlugGenerator not set.');
        }
        $slug = $this->slugGenerator->generate($slug);
        foreach ($values as $value) {
            if ($this->slugGenerator->generate($value) === $slug) {
                return $value;
            }
        }
        return '';
    }
}
