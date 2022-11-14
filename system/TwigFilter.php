<?php

declare(strict_types=1);

namespace herbie;

final class TwigFilter
{
    private string $name;
    /** @var callable */
    private $callable;
    /** @var array<string, scalar|null> */
    private array $options;

    /**
     * @param array<string, scalar|null> $options
     */
    public function __construct(string $name, callable $callable, array $options = [])
    {
        $this->name = $name;
        $this->callable = $callable;
        $this->options = $options;
    }

    public function createTwigFilter(): \Twig\TwigFilter
    {
        return new \Twig\TwigFilter($this->name, $this->callable, $this->options);
    }
}