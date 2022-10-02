<?php

declare(strict_types=1);

namespace herbie;

interface PageRepositoryInterface
{
    public function find(string $id): ?Page;

    public function findAll(): PageList;

    public function save(Page $page): bool;

    public function delete(Page $page): bool;
}
