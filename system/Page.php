<?php

declare(strict_types=1);

namespace herbie;

/**
 * Stores the page.
 * @property string[] $segments
 */
final class Page
{
    use PageItemTrait;

    private array $segments = [];

    public function __construct(array $data = [], array $segments = [])
    {
        $this->initData($data);
        $this->setSegments($segments);
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
        return (string)$segment;
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
        if (array_key_exists('data', $data)) {
            throw new \InvalidArgumentException("Field data is not allowed.");
        }
        if (array_key_exists('segments', $data)) {
            throw new \InvalidArgumentException("Field segments is not allowed.");
        }
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }
    }
}
