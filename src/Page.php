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

namespace Herbie;

use Herbie\Menu\MenuItemTrait;

/**
 * Stores the page.
 */
class Page
{
    use MenuItemTrait;

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
     * @return StringValue
     */
    public function getSegment(string $id): StringValue
    {
        $segment = new StringValue();
        if (array_key_exists($id, $this->segments)) {
            $segment->set($this->segments[$id]);
        }
        return $segment;
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
            throw new \LogicException("Field segments is not allowed.");
        }
        if (array_key_exists('data', $data)) {
            throw new \LogicException("Field data is not allowed.");
        }
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parent' => $this->parent,
            'data' => $this->data,
            'segments' => $this->segments
        ];
    }

    /**
     * Get the http status code depending on a set error code.
     * @return int
     */
    public function getStatusCode(): int
    {
        if (empty($this->data['error'])) {
            return 200;
        }
        if (empty($this->data['error']['code'])) {
            return 500;
        }
        return $this->data['error']['code'];
    }

    /**
     * @return \Throwable
     */
    public function getError(): \Throwable
    {
        return $this->data['error'];
    }

    /**
     * @param \Throwable $e
     */
    public function setError(\Throwable $e): void
    {
        $this->data['error'] = [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
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
