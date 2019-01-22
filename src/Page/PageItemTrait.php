<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie\Page;

use Ausi\SlugGenerator\SlugGenerator;
use BadMethodCallException;
use function Herbie\camelize;
use InvalidArgumentException;

trait PageItemTrait
{
    private $authors;
    private $categories;
    private $content_type;
    private $created;
    private $customData;
    private $date;
    private $excerpt;
    private $format;
    private $hidden;
    private $keep_extension;
    private $layout;
    private $menu;
    private $modified;
    private $cached;
    private $path;
    private $redirect;
    private $route;
    private $tags;
    private $title;
    private $type;
    private $twig;

    /**
     * @var SlugGenerator
     */
    private static $slugGenerator;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        // set defaults
        $this->title = '';
        $this->route = '';
        $this->type = 'page';
        $this->path = '';
        $this->format = '';
        $this->date = '';
        $this->layout = 'default';
        $this->content_type = 'text/html';
        $this->authors = [];
        $this->categories = [];
        $this->tags = [];
        $this->menu = '';
        $this->modified = '';
        $this->created = '';
        $this->cached = 1;
        $this->hidden = 0;
        $this->excerpt = '';
        $this->twig = 1;
        $this->keep_extension = 0;
        $this->customData = [];
        $this->redirect = [];

        // set values
        $this->setData($data);
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = trim($title);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = trim($type);
    }

    /**
     * @return string
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * @param string $layout
     */
    public function setLayout(string $layout): void
    {
        $this->layout = trim($layout);
    }

    /**
     * @return string
     */
    public function getMenu(): string
    {
        return $this->menu;
    }

    /**
     * @param string $menu
     */
    public function setMenu(string $menu): void
    {
        $this->menu = trim($menu);
    }

    /**
     * @return array
     */
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
                throw new InvalidArgumentException($message);
            }
        }
        $this->redirect = $redirectArray;
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return trim($this->route);
    }

    /**
     * @param string $route
     */
    public function setRoute(string $route): void
    {
        $this->route = trim($route);
    }

    /**
     * @return string
     */
    public function getParentRoute(): string
    {
        $parentRoute = trim(dirname($this->getRoute()), '.');
        return $parentRoute;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = trim($path);
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param string $format
     */
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
            default:
                $format = 'raw';
        }
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date): void
    {
        $this->date = is_numeric($date) ? date('c', $date) : trim($date);
    }

    /**
     * @return string
     */
    public function getMenuTitle(): string
    {
        if (!empty($this->menu)) {
            return $this->menu;
        }
        return $this->title;
    }

    /**
     * @param string $author
     * @return string
     */
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

    /**
     * @return array
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * @param string $category
     * @return string
     */
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

    /**
     * @return array
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param string $tag
     * @return string
     */
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
     * @param array $categories
     */
    public function setCategories(array $categories): void
    {
        $categories = array_map('trim', $categories);
        $categories = array_unique($categories);
        $this->categories = $categories;
    }

    /**
     * @param string $category
     */
    public function setCategory(string $category): void
    {
        $category = trim($category);
        if (!in_array($category, $this->categories)) {
            $this->categories[] = $category;
        }
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags): void
    {
        $tags = array_map('trim', $tags);
        $tags = array_unique($tags);
        $this->tags = $tags;
    }

    /**
     * @param string $tag
     */
    public function setTag(string $tag): void
    {
        $tag = trim($tag);
        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }
    }


    /**
     * @param array $authors
     */
    public function setAuthors(array $authors): void
    {
        $authors = array_map('trim', $authors);
        $authors = array_unique($authors);
        $this->authors = $authors;
    }

    /**
     * @param string $author
     */
    public function setAuthor(string $author): void
    {
        $author = trim($author);
        if (!in_array($author, $this->authors)) {
            $this->authors[] = $author;
        }
    }

    /**
     * @param string $author
     * @return boolean
     */
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

    /**
     * @param string $category
     * @return boolean
     */
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

    /**
     * @param string $tag
     * @return boolean
     */
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

    /**
     * @param string $modified
     */
    public function setModified(string $modified): void
    {
        $this->modified = $modified;
    }

    /**
     * @return string
     */
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
     * @param array $data
     * @throws \LogicException
     */
    private function setData(array $data): void
    {
        if (array_key_exists('data', $data)) {
            throw new \LogicException("Field data is not allowed.");
        }
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }
    }

    /**
     * @return bool
     */
    public function isStartPage(): bool
    {
        return $this->getRoute() == '';
    }

    /**
     * @param string $route
     * @return bool
     */
    public function routeEquals(string $route): bool
    {
        return $this->getRoute() == $route;
    }

    /**
     * @param string $route
     * @return bool
     */
    public function routeInPageTrail(string $route): bool
    {
        $current = $this->getRoute();
        if (empty($route) || empty($current)) {
            return false;
        }
        return 0 === strpos($route, $current);
    }

    /**
     * @return bool
     */
    public function isStaticPage(): bool
    {
        return 0 === strpos($this->getPath(), '@page');
    }

    /**
     * @param $name
     * @return mixed
     * @throws \LogicException
     */
    public function __get(string $name)
    {
        $getter = 'get' . camelize($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (array_key_exists($name, $this->customData)) {
            return $this->customData[$name];
        } else {
            throw new \LogicException("Field {$name} does not exist.");
        }
    }

    /**
     * @param string $name
     * @return boolean
     */
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
     * @param string $name
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

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->title;
    }

    /**
     * @return array
     */
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

    /**
     * @param string $slug
     * @return string
     */
    private function slugify(string $slug): string
    {
        return static::$slugGenerator->generate($slug);
    }

    /**
     * @param SlugGenerator $slugGenerator
     */
    public static function setSlugGenerator(SlugGenerator $slugGenerator)
    {
        if (!empty(static::$slugGenerator)) {
            throw new BadMethodCallException('SlugGenerator already set');
        }
        static::$slugGenerator = $slugGenerator;
    }
}
