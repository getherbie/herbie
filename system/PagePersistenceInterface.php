<?php

declare(strict_types=1);

namespace herbie;

interface PagePersistenceInterface
{
    public function findById(string $id): ?array;

    public function findAll(): array;
}
