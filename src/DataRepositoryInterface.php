<?php

declare(strict_types=1);

namespace herbie;

interface DataRepositoryInterface
{
    public function __construct(array $options = []);
    public function load(string $name): array;
    public function loadAll(): array;
}
