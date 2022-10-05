<?php

declare(strict_types=1);

namespace herbie;

class TwigFunction
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

    public function createTwigFunction(): \Twig\TwigFunction
    {
        return new \Twig\TwigFunction($this->name, $this->callable, $this->options);
    }
}
