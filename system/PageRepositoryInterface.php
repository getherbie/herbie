<?php

declare(strict_types=1);

namespace herbie;

interface PageRepositoryInterface
{
    /**
     * @param string $id The aliased unique path to the file (i.e. @page/about/company.md)
     */
    public function find(string $id): ?Page;

    public function findAll(): PageList;
}
