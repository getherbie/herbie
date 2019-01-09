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

namespace Herbie\Menu;

trait MenuItemTrait
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        // default / required fields
        $this->data = [
            'title' => '',
            'route' => '',
            'path' => '',
            'format' => '',
            'date' => '',
            'layout' => 'default',
            'content_type' => 'text/html',
            'authors' => [],
            'categories' => [],
            'tags' => [],
            'menu' => '',
            'modified' => '',
            'created' => '',
            'nocache' => 0,
            'hidden' => 0,
            'excerpt' => '',
            'twig' => 0,
            'keep_extension' => 0,
            'error' => []
        ];
        $this->setData($data);
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->data['title'] ?? '';
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->data['title'] = trim($title);
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return trim($this->data['route']);
    }
    /**
     * @return string
     * TODO do we need this?
     */
    public function __getRoute(): string
    {
        $route = trim(basename($this->getPath()), '/');
        $route = preg_replace('/^([0-9]{4})-([0-9]{2})-([0-9]{2})(.*)$/', '\\1/\\2/\\3\\4', $route);

        // Endung entfernen
        $pos = strrpos($route, '.');
        if ($pos !== false) {
            $route = substr($route, 0, $pos);
        }

        if (empty($this->blogRoute)) {
            return $route;
        }
        return $this->blogRoute . '/' . $route;
    }

    /**
     * @param string $route
     */
    public function setRoute(string $route): void
    {
        $this->data['route'] = $route;
    }

    /**
     * @return string
     */
    public function getParentRoute(): string
    {
        return trim(dirname($this->getRoute()), '.');
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return isset($this->data['path']) ? $this->data['path'] : '';
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->data['path'] = $path;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->data['format'];
    }

    /**
     * @param string $format
     */
    public function setFormat(string $format): void
    {
        switch ($format) {
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
        $this->data['format'] = $format;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return isset($this->data['date']) ? $this->data['date'] : '';
    }

    /**
     * @param string $date
     */
    public function setDate($date): void
    {
        $this->data['date'] = is_numeric($date) ? date('c', $date) : $date;
    }

    /**
     * @return string
     */
    public function getMenuTitle(): string
    {
        if (!empty($this->data['menu'])) {
            return $this->data['menu'];
        }
        return $this->data['title'];
    }

    /**
     * @param string $author
     * @return string
     */
    public function getAuthor(string $author): string
    {
        $author = $this->slugify($author);
        foreach ($this->data['authors'] as $a) {
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
        return $this->data['authors'];
    }

    /**
     * @param string $category
     * @return string
     */
    public function getCategory(string $category): string
    {
        $category = $this->slugify($category);
        foreach ($this->data['categories'] as $c) {
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
        return $this->data['categories'];
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return isset($this->data['tags']) ? $this->data['tags'] : [];
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
        $this->data['categories'] = array_unique($categories);
    }

    /**
     * @param string $category
     */
    public function setCategory(string $category): void
    {
        $this->data['categories'][] = $category;
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags): void
    {
        $this->data['tags'] = array_unique($tags);
    }

    /**
     * @param string $tag
     */
    public function setTag(string $tag): void
    {
        $this->data['tags'][] = $tag;
    }


    /**
     * @param array $authors
     */
    public function setAuthors(array $authors): void
    {
        $this->data['authors'] = array_unique($authors);
    }

    /**
     * @param string $author
     */
    public function setAuthor(string $author): void
    {
        $this->data['authors'][] = $author;
    }

    /**
     * @param string $author
     * @return boolean
     */
    public function hasAuthor(string $author): bool
    {
        $author = $this->slugify($author);
        foreach ($this->data['authors'] as $c) {
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
        foreach ($this->data['categories'] as $c) {
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
        $this->data['modified'] = $modified;
    }

    /**
     * @return string
     */
    public function getModified(): string
    {
        return isset($this->data['modified']) ? $this->data['modified'] : '';
    }

    public function getTwig(): int
    {
        return isset($this->data['twig']) ? $this->data['twig'] : 0;
    }

    public function setTwig(int $twig): void
    {
        $this->data['twig'] = abs($twig);
    }

    public function getKeepExtension(): int
    {
        return isset($this->data['keep_extension']) ? $this->data['keep_extension'] : 0;
    }

    public function setKeepExtension(int $keepExtension): void
    {
        $this->data['keep_extension'] = abs($keepExtension);
    }

    public function getContentType(): string
    {
        return isset($this->data['content_type']) ? $this->data['content_type'] : 'text/html';
    }

    public function setContentType(string $contentType): void
    {
        $this->data['content_type'] = string($contentType);
    }

    public function getNoCache(): int
    {
        return isset($this->data['nocache']) ? $this->data['nocache'] : 0;
    }

    public function setNoCache(int $noCache): void
    {
        $this->data['nocache'] = abs($noCache);
    }

    public function getHidden(): int
    {
        return isset($this->data['hidden']) ? $this->data['hidden'] : 0;
    }

    public function setHidden(int $hidden): void
    {
        $this->data['hidden'] = abs($hidden);
    }

    public function getExcerpt(): string
    {
        return isset($this->data['excerpt']) ? $this->data['excerpt'] : '';
    }

    public function setExcerpt(string $excerpt): void
    {
        $this->data['excerpt'] = trim($excerpt);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @throws \LogicException
     */
    public function setData(array $data): void
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
    public function routeInMenuTrail(string $route): bool
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
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (array_key_exists($name, $this->data)) {
            return $this->data[$name];
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
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } elseif (array_key_exists($name, $this->data)) {
            return $this->data[$name] !== null;
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
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            $this->data[$name] = $value;
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->data['title'];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @param string $slug
     * @return string
     */
    private function slugify(string $slug): string
    {
        // TODO use SlugGeneratorInterface
        return strtolower($slug);
    }
}
