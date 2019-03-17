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
 */
class Page
{
    use PageItemTrait;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $parent;

    /**
     * @var array
     */
    private $segments = [];

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getParent(): string
    {
        return $this->parent;
    }

    /**
     * @param string $parent
     */
    public function setParent(string $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return array
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     *
     * @param string $id
     * @return string
     */
    public function getSegment(string $id): string
    {
        $segment = '';
        if (array_key_exists($id, $this->segments)) {
            $segment = $this->segments[$id];
        }
        return strval($segment);
    }

    /**
     * @param array $segments
     */
    public function setSegments(array $segments = []): void
    {
        $this->segments = $segments;
    }

    /**
     * Overwrites MenuItemTrait::setData()
     * @param array $data
     * @throws \LogicException
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
