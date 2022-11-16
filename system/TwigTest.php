<?php

declare(strict_types=1);

namespace herbie;

final class TwigTest
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

    public function createTwigTest(): \Twig\TwigTest
    {
        return new \Twig\TwigTest($this->name, $this->callable, $this->options);
    }
}
