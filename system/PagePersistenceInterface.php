<?php

declare(strict_types=1);

namespace herbie;

interface PagePersistenceInterface
{
    public function add(string $id, array $data): ?array;
    public function findById(string $id): ?array;
    public function findAll(): array;
}
