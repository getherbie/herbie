<?php

declare(strict_types=1);

namespace herbie;

use ArrayAccess;
use Ausi\SlugGenerator\SlugGenerator;
use BadMethodCallException;
use InvalidArgumentException;
use ReturnTypeWillChange;

/**
 * @property string[] $authors
 * @property bool $cached
 * @property-read string $cacheId
 * @property string[] $categories
 * @property string $content_type
 * @property string $created
 * @property array<int|string, mixed> $customData
 * @property string $date
 * @property string $excerpt
 * @property string $format
 * @property bool $hidden
 * @property string $id
 * @property bool $keep_extension
 * @property string $layout
 * @property string $menu_title
 * @property string $modified
 * @property string $parent_id
 * @property string $parent_route
 * @property string $path
 * @property array $redirect
 * @property string $route
 * @property null|string[] $segments
 * @property string[] $tags
 * @property string $title
 * @property bool $twig
 * @property string $type
 */
final class Page implements ArrayAccess
{
    private static ?SlugGenerator $slugGenerator = null;
    /** @var string[] */
    private array $authors;
    private bool $cached;
    /** @var string[] */
    private array $categories;
    private string $content_type;
    private string $created;
    /** @var array<int|string, mixed> */
    private array $customData;
    private string $date;
    private string $excerpt;
    private string $format;
    private bool $hidden;
    private string $id;
    private bool $keep_extension;
    private string $layout;
    private string $menu_title;
    private string $modified;
    private string $parent_id;
    private string $parent_route;
    private string $path;
    private array $redirect;
    private string $route;
    /** @var null|string[] */
    private ?array $segments;
    /** @var string[] */
    private array $tags;
    private string $title;
    private bool $twig;
    private string $type;

    public function __construct(array $data = [], ?array $segments = null)
    {
        // set defaults
        $this->authors = [];
        $this->cached = true;
        $this->categories = [];
        $this->content_type = 'text/html';
        $this->created = '';
        $this->customData = [];
        $this->date = '';
        $this->excerpt = '';
        $this->format = 'raw';
        $this->hidden = false;
        $this->id = '';
        $this->keep_extension = false;
        $this->layout = 'default';
        $this->menu_title = '';
        $this->modified = '';
        $this->parent_id = '';
        $this->parent_route = '';
        $this->path = '';
        $this->redirect = [];
        $this->route = '';
        $this->segments = null;
        $this->tags = [];
        $this->title = '';
        $this->twig = true;
        $this->type = 'page';

        $this->setData($data);
        if ($segments !== null) {
            $this->setSegments($segments);
        }
    }

    /**
     * Overwrites PageItemTrait::setData()
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
            $this->__set($key, $value);
        }
    }

    public static function setSlugGenerator(SlugGenerator $slugGenerator): void
    {
        self::$slugGenerator = $slugGenerator;
    }

    public static function unsetSlugGenerator(): void
    {
        self::$slugGenerator = null;
    }

    public function getSegment(string $id): string
    {
        $segments = $this->getSegments();
        if (array_key_exists($id, $segments)) {
            return (string)$segments[$id];
        }
        return '';
    }

    public function getSegments(): array
    {
        if ($this->segments === null) {
            $content = file_read($this->getPath());
            [, $this->segments] = FlatFilePagePersistence::parseFileContent($content);
        }
        return $this->segments;
    }

    /**
     * @param string[] $segments
     */
    public function setSegments(array $segments = []): void
    {
        $this->segments = $segments;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = trim($path);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = trim($title);
    }

    public function getCacheId(): string
    {
        return 'page-' . $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = trim($type);
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function setLayout(string $layout): void
    {
        $this->layout = trim($layout);
    }

    public function getMenuTitle(): string
    {
        if ($this->menu_title !== '') {
            return $this->menu_title;
        }
        return $this->title;
    }

    public function setMenuTitle(string $menuTitle): void
    {
        $this->menu_title = trim($menuTitle);
    }

    public function getRedirect(): array
    {
        return $this->redirect;
    }

    /**
     * @param string|array $redirect
     */
    public function setRedirect($redirect): void
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
        $count = count($redirect);
        if ($count === 0) {
            throw new InvalidArgumentException('Redirect must be a non-empty array.');
        }
        if ($count <> 2) {
            throw new InvalidArgumentException('Redirect array must be an array{string,int}.');
        }
        if (!is_string($redirect[0])) {
            throw new InvalidArgumentException('Redirect array[0] must be a string.');
        }
        $redirect[0] = trim($redirect[0]);
        if ($redirect[0] === '') {
            throw new InvalidArgumentException('Redirect array[0] must be a non-empty string.');
        }
        if (!is_natural($redirect[1])) {
            throw new InvalidArgumentException('Redirect array[1] must be a integer.');
        }
        if ($redirect[1] < 300 || $redirect[1] > 308) {
            throw new InvalidArgumentException('Redirect array[1] must be a status code between 300 and 308.');
        }
        $this->redirect = $redirect;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = trim($id);
    }

    public function getParentId(): string
    {
        return $this->parent_id;
    }

    public function setParentId(string $parentId): void
    {
        $this->parent_id = trim($parentId);
    }

    public function getParentRoute(): string
    {
        return $this->parent_route;
    }

    public function setParentRoute(string $parentRoute): void
    {
        $this->parent_route = trim($parentRoute);
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): void
    {
        switch (trim($format)) {
            case 'md':
            case 'markdown':
                $format = 'markdown';
                break;
            case 'textile':
                $format = 'textile';
                break;
            case 'rst':
                $format = 'rest';
                break;
            default:
                $format = 'raw';
        }
        $this->format = $format;
    }

    public function getCreated(): string
    {
        return $this->created;
    }

    /**
     * @param int|string $date
     */
    public function setCreated($date): void
    {
        $this->created = $this->formatDate($date);
    }

    /**
     * @param int|string $date
     */
    private function formatDate($date): string
    {
        if (is_string($date)) {
            $date = trim($date);
        }
        if (is_natural($date, true)) {
            return date_format('c', (int)$date);
        } elseif (is_string($date)) {
            $time = time_from_string($date);
            if ($time > 0) {
                return date_format('c', $time);
            }
        }
        return '';
    }

    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param int|string $date
     */
    public function setDate($date): void
    {
        $this->date = $this->formatDate($date);
    }

    public function getAuthor(string $author): string
    {
        $author = $this->slugify($author);
        foreach ($this->authors as $a) {
            if ($this->slugify($a) === $author) {
                return $a;
            }
        }
        return '';
    }

    private function slugify(string $slug): string
    {
        if (self::$slugGenerator === null) {
            throw new BadMethodCallException('SlugGenerator not set.');
        }
        return self::$slugGenerator->generate($slug);
    }

    public function getCategory(string $category): string
    {
        $category = $this->slugify($category);
        foreach ($this->categories as $c) {
            if ($this->slugify($c) === $category) {
                return $c;
            }
        }
        return '';
    }

    public function getTag(string $tag): string
    {
        $tag = $this->slugify($tag);
        foreach ($this->getTags() as $t) {
            if ($this->slugify($t) === $tag) {
                return $t;
            }
        }
        return '';
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param string[] $tags
     */
    public function setTags(array $tags): void
    {
        foreach ($tags as $tag) {
            $this->setTag($tag);
        }
    }

    public function setTag(string $tag): void
    {
        $tag = trim($tag);
        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }
    }

    public function hasAuthor(string $author): bool
    {
        $author = $this->slugify($author);
        foreach ($this->getAuthors() as $c) {
            if ($this->slugify($c) === $author) {
                return true;
            }
        }
        return false;
    }

    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * @param string[] $authors
     */
    public function setAuthors(array $authors): void
    {
        foreach ($authors as $author) {
            $this->setAuthor($author);
        }
    }

    public function setAuthor(string $author): void
    {
        $author = trim($author);
        if (!in_array($author, $this->authors)) {
            $this->authors[] = $author;
        }
    }

    public function hasCategory(string $category): bool
    {
        $category = $this->slugify($category);
        foreach ($this->getCategories() as $c) {
            if ($this->slugify($c) === $category) {
                return true;
            }
        }
        return false;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param string[] $categories
     */
    public function setCategories(array $categories): void
    {
        foreach ($categories as $category) {
            $this->setCategory($category);
        }
    }

    public function setCategory(string $category): void
    {
        $category = trim($category);
        if (!in_array($category, $this->categories)) {
            $this->categories[] = $category;
        }
    }

    public function hasTag(string $tag): bool
    {
        $tag = $this->slugify($tag);
        foreach ($this->getTags() as $t) {
            if ($this->slugify($t) === $tag) {
                return true;
            }
        }
        return false;
    }

    public function getModified(): string
    {
        return $this->modified;
    }

    /**
     * @param int|string $modified
     */
    public function setModified($modified): void
    {
        $this->modified = $this->formatDate($modified);
    }

    public function getTwig(): bool
    {
        return $this->twig;
    }

    public function setTwig(bool $twig): void
    {
        $this->twig = $twig;
    }

    public function getKeepExtension(): bool
    {
        return $this->keep_extension;
    }

    public function setKeepExtension(bool $keepExtension): void
    {
        $this->keep_extension = $keepExtension;
    }

    public function getContentType(): string
    {
        return $this->content_type;
    }

    public function setContentType(string $contentType): void
    {
        $this->content_type = trim($contentType);
    }

    public function getCached(): bool
    {
        return $this->cached;
    }

    public function setCached(bool $cached): void
    {
        $this->cached = $cached;
    }

    public function getHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    public function setExcerpt(string $excerpt): void
    {
        $this->excerpt = trim($excerpt);
    }

    public function isStartPage(): bool
    {
        return $this->getRoute() === '';
    }

    public function getRoute(): string
    {
        return trim($this->route);
    }

    public function setRoute(string $route): void
    {
        $this->route = trim($route);
    }

    public function routeEquals(string $route): bool
    {
        return $this->getRoute() === $route;
    }

    public function routeInPageTrail(string $route): bool
    {
        $current = $this->getRoute();
        if (empty($route) || empty($current)) {
            return false;
        }
        return 0 === strpos($route, $current);
    }

    public function isStaticPage(): bool
    {
        return 0 === strpos($this->getPath(), '@page');
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function toArray(): array
    {
        $array = [];
        foreach (get_object_vars($this) as $name => $value) {
            $method = 'get' . str_replace('_', '', $name);
            if (method_exists($this, $method)) {
                $array[$name] = $this->$method();
            }
        }
        return array_merge($array, $this->customData);
    }

    /**
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        return $this->__isset($offset);
    }

    public function __isset(string $name): bool
    {
        $getter = 'get' . str_replace('_', '', $name);
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } elseif (array_key_exists($name, $this->customData)) {
            return $this->customData[$name] !== null;
        } else {
            return false;
        }
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        $getter = 'get' . str_replace('_', '', $name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (array_key_exists($name, $this->customData)) {
            return $this->customData[$name];
        } else {
            throw new InvalidArgumentException("Field {$name} does not exist.");
        }
    }

    /**
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        $setter = 'set' . str_replace('_', '', $name);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            $this->customData[$name] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->__set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('Unset is not supported.');
    }
}
