<?php

declare(strict_types=1);

namespace herbie;

interface DataRepositoryInterface
{
    public function __construct(string $path);

    public function load(string $name): array;

    public function loadAll(): array;
}
