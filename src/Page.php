<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

/**
 * Stores the page.
 * @property string $id
 * @property string $parent
 * @property string[] $segments
 */
final class Page
{
    use PageItemTrait;

    private string $id;
    private string $parent;
    private array $segments = [];

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getParent(): string
    {
        return $this->parent;
    }

    public function setParent(string $parent): void
    {
        $this->parent = $parent;
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function getSegment(string $id): string
    {
        $segment = '';
        if (array_key_exists($id, $this->segments)) {
            $segment = $this->segments[$id];
        }
        return strval($segment);
    }

    /**
     * @param string[] $segments
     */
    public function setSegments(array $segments = []): void
    {
        $this->segments = $segments;
    }

    /**
     * Overwrites PageItemTrait::setData()
     */
    public function setData(array $data): void
    {
        if (array_key_exists('segments', $data)) {
            throw new \InvalidArgumentException("Field segments is not allowed.");
        }
        if (array_key_exists('data', $data)) {
            throw new \InvalidArgumentException("Field data is not allowed.");
        }
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }
    }

    /**
     * @return string
     */
    public function getDefaultBlocksPath(): string
    {
        $pathInfo = pathinfo($this->getPath());
        return $pathInfo['dirname'] . '/_' . $pathInfo['filename'];
    }
}
