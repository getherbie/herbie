<?php

declare(strict_types=1);

namespace herbie;

class TwigTest
{
    private string $name;
    private $callable;
    private array $options;

    public function __construct(string $name, callable $callable = null, array $options = [])
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
