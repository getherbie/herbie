<?php

declare(strict_types=1);

namespace herbie;

interface PageRepositoryInterface
{
    public function getPage(string $id): ?Page;

    public function getMenuList(): MenuList;

    public function savePage(Page $page): bool;

    public function deletePage(Page $page): bool;
}
