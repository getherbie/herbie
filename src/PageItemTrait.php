<?php

declare(strict_types=1);

namespace herbie;

use Ausi\SlugGenerator\SlugGenerator;

/**
 * @property string[] $authors
 * @property int $cached
 * @property string[] $categories
 * @property string $content_type
 * @property string $created
 * @property array<int|string, mixed> $customData
 * @property string $date
 * @property string $excerpt
 * @property string $format
 * @property int $hidden
 * @property int $keep_extension
 * @property string $layout
 * @property string $menu
 * @property string $modified
 * @property string $path
 * @property array<void>|array{status: int, url: string} $redirect
 * @property string $route
 * @property string[] $tags
 * @property string $title
 * @property int $twig
 * @property string $type
 */
trait PageItemTrait
{
    /** @var string[] */
    private array $authors;
    private int $cached;
    /** @var string[] */
    private array $categories;
    private string $content_type;
    private string $created;
    /** @var array<int|string, mixed> */
    private array $customData;
    private string $date;
    private string $excerpt;
    private string $format;
    private int $hidden;
    private int $keep_extension;
    private string $layout;
    private string $menu;
    private string $modified;
    private string $path;
    /** @var array{status: int, url: string} */
    private array $redirect;
    private string $route;
    /** @var string[] */
    private array $tags;
    private string $title;
    private int $twig;
    private string $type;

    private static ?SlugGenerator $slugGenerator;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        // set defaults
        $this->authors = [];
        $this->cached = 1;
        $this->categories = [];
        $this->content_type = 'text/html';
        $this->created = '';
        $this->customData = [];
        $this->date = '';
        $this->excerpt = '';
        $this->format = '';
        $this->hidden = 0;
        $this->keep_extension = 0;
        $this->layout = 'default';
        $this->menu = '';
        $this->modified = '';
        $this->path = '';
        $this->redirect = [];
        $this->route = '';
        $this->tags = [];
        $this->title = '';
        $this->twig = 1;
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

    public function getMenu(): string
    {
        return $this->menu;
    }

    public function setMenu(string $menu): void
    {
        $this->menu = trim($menu);
    }

    public function getRedirect(): array
    {
        return $this->redirect;
    }

    /**
     * @param array|string $redirect
     */
    public function setRedirect($redirect): void
    {
        if (is_string($redirect)) {
            $redirectArray = [
                'status' => 302,
                'url' => $redirect
            ];
        } else {
            $redirectArray = $redirect;
        }
        foreach ($redirectArray as $key => $value) {
            if (!in_array($key, ['url', 'status'])) {
                $message = sprintf('Key "%s" not allowed', $key);
                throw new \InvalidArgumentException($message);
            }
        }
        $this->redirect = $redirectArray;
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

    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param int|string $date
     */
    public function setDate($date): void
    {
        $this->date = is_numeric($date) ? date('c', $date) : trim($date);
    }

    public function getMenuTitle(): string
    {
        if (!empty($this->menu)) {
            return $this->menu;
        }
        return $this->title;
    }

    public function getAuthor(string $author): string
    {
        $author = $this->slugify($author);
        foreach ($this->authors as $a) {
            if ($this->slugify($a) == $author) {
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
            if ($this->slugify($c) == $category) {
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
            if ($this->slugify($t) == $tag) {
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
        $categories = array_map('trim', $categories);
        $categories = array_unique($categories);
        $this->categories = $categories;
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
        $tags = array_map('trim', $tags);
        $tags = array_unique($tags);
        $this->tags = $tags;
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
        $authors = array_map('trim', $authors);
        $authors = array_unique($authors);
        $this->authors = $authors;
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
            if ($this->slugify($c) == $author) {
                return true;
            }
        }
        return false;
    }

    public function hasCategory(string $category): bool
    {
        $category = $this->slugify($category);
        foreach ($this->getCategories() as $c) {
            if ($this->slugify($c) == $category) {
                return true;
            }
        }
        return false;
    }

    public function hasTag(string $tag): bool
    {
        $tag = $this->slugify($tag);
        foreach ($this->getTags() as $t) {
            if ($this->slugify($t) == $tag) {
                return true;
            }
        }
        return false;
    }

    public function setModified(string $modified): void
    {
        $this->modified = $modified;
    }

    public function getModified(): string
    {
        return $this->modified;
    }

    public function getTwig(): int
    {
        return $this->twig;
    }

    public function setTwig(int $twig): void
    {
        $this->twig = abs($twig);
    }

    public function getKeepExtension(): int
    {
        return $this->keep_extension;
    }

    public function setKeepExtension(int $keepExtension): void
    {
        $this->keep_extension = abs($keepExtension);
    }

    public function getContentType(): string
    {
        return $this->content_type;
    }

    public function setContentType(string $contentType): void
    {
        $this->content_type = trim($contentType);
    }

    public function getCached(): int
    {
        return $this->cached;
    }

    public function setCached(int $cached): void
    {
        $this->cached = abs($cached);
    }

    public function getHidden(): int
    {
        return $this->hidden;
    }

    public function setHidden(int $hidden): void
    {
        $this->hidden = abs($hidden);
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
        $getter = 'get' . camelize($name);
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
        $getter = 'get' . camelize($name);
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
        $setter = 'set' . camelize($name);
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
            $method = 'get' . camelize($name);
            if (method_exists($this, $method)) {
                $array[$name] = $this->$method();
            }
        }
        return $array;
    }

    private function slugify(string $slug): string
    {
        if (is_null(self::$slugGenerator)) {
            throw new \BadMethodCallException('SlugGenerator not set');
        }
        return self::$slugGenerator->generate($slug);
    }

    public static function setSlugGenerator(SlugGenerator $slugGenerator): void
    {
        self::$slugGenerator = $slugGenerator;
    }
}
