<?php

declare(strict_types=1);

namespace herbie;

use Ausi\SlugGenerator\SlugGenerator;

/**
 * @property string[] $authors
 * @property bool $cached
 * @property string[] $categories
 * @property string $content_type
 * @property string $created
 * @property array<int|string, mixed> $customData
 * @property string $date
 * @property string $excerpt
 * @property string $format
 * @property bool $hidden
 * @property bool $keep_extension
 * @property string $layout
 * @property string $menu_title
 * @property string $modified
 * @property string $path
 * @property array<void>|array{status: int, url: string} $redirect
 * @property string $route
 * @property string[] $tags
 * @property string $title
 * @property bool $twig
 * @property string $type
 */
trait PageItemTrait
{
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
    private bool $keep_extension;
    private string $layout;
    private string $menu_title;
    private string $modified;
    private string $path;
    /** @var array{string, int} */
    private array $redirect;
    private string $route;
    /** @var string[] */
    private array $tags;
    private string $title;
    private bool $twig;
    private string $type;

    private static ?SlugGenerator $slugGenerator = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
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
        $this->keep_extension = false;
        $this->layout = 'default';
        $this->menu_title = '';
        $this->modified = '';
        $this->path = '';
        $this->redirect = [];
        $this->route = '';
        $this->tags = [];
        $this->title = '';
        $this->twig = true;
        $this->type = 'page';

        // set values
        $this->setData($data);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = trim($title);
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
        if (strlen($this->menu_title) > 0) {
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
     * @param string|array|mixed $redirect
     */
    public function setRedirect($redirect): void
    {
        if (!is_array($redirect) && !is_string($redirect)) {
            throw new \InvalidArgumentException('Redirect must be a string or an array{string,int}.');
        }
        if (is_string($redirect)) {
            $redirect = trim($redirect);
            if (strlen($redirect) === 0) {
                throw new \InvalidArgumentException('Redirect must be a non-empty string.');
            }
            $redirect = [$redirect, 302];
        }
        $count = count($redirect);
        if ($count === 0) {
            throw new \InvalidArgumentException('Redirect must be a non-empty array.');
        }
        if ($count <> 2) {
            throw new \InvalidArgumentException('Redirect array must be an array{string,int}.');
        }
        if (!is_string($redirect[0])) {
            throw new \InvalidArgumentException('Redirect array[0] must be a string.');
        }
        $redirect[0] = trim($redirect[0]);
        if (strlen($redirect[0]) === 0) {
            throw new \InvalidArgumentException('Redirect array[0] must be a non-empty string.');
        }
        if (!is_natural($redirect[1])) {
            throw new \InvalidArgumentException('Redirect array[1] must be a integer.');
        }
        if ($redirect[1] < 300 || $redirect[1] > 308) {
            throw new \InvalidArgumentException('Redirect array[1] must be a status code between 300 and 308.');
        }
        $this->redirect = $redirect;
    }

    public function getRoute(): string
    {
        return trim($this->route);
    }

    public function setRoute(string $route): void
    {
        $this->route = trim($route);
    }

    public function getParentRoute(): string
    {
        return trim(dirname($this->getRoute()), '.');
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = trim($path);
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

    public function getAuthors(): array
    {
        return $this->authors;
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

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getTags(): array
    {
        return $this->tags;
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

    /**
     * @param int|string $modified
     */
    public function setModified($modified): void
    {
        $this->modified = $this->formatDate($modified);
    }

    public function getModified(): string
    {
        return $this->modified;
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

    /**
     * @param array<string, mixed> $data
     */
    private function setData(array $data): void
    {
        if (array_key_exists('data', $data)) {
            throw new \InvalidArgumentException("Field data is not allowed.");
        }
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }
    }

    public function isStartPage(): bool
    {
        return $this->getRoute() === '';
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

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        $getter = 'get' . str_camelize($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (array_key_exists($name, $this->customData)) {
            return $this->customData[$name];
        } else {
            throw new \InvalidArgumentException("Field {$name} does not exist.");
        }
    }

    public function __isset(string $name): bool
    {
        $getter = 'get' . str_camelize($name);
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } elseif (array_key_exists($name, $this->customData)) {
            return $this->customData[$name] !== null;
        } else {
            return false;
        }
    }

    /**
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        $setter = 'set' . str_camelize($name);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            $this->customData[$name] = $value;
        }
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function toArray(): array
    {
        $array = [];
        foreach (get_object_vars($this) as $name => $value) {
            $method = 'get' . str_camelize($name);
            if (method_exists($this, $method)) {
                $array[$name] = $this->$method();
            }
        }
        return array_merge($array, $this->customData);
    }

    private function slugify(string $slug): string
    {
        if (is_null(self::$slugGenerator)) {
            throw new \BadMethodCallException('SlugGenerator not set.');
        }
        return self::$slugGenerator->generate($slug);
    }

    public static function setSlugGenerator(SlugGenerator $slugGenerator): void
    {
        self::$slugGenerator = $slugGenerator;
    }

    public static function unsetSlugGenerator(): void
    {
        self::$slugGenerator = null;
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
}
