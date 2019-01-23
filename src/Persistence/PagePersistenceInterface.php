<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 31.12.18
 * Time: 15:14
 */

declare(strict_types=1);

namespace Herbie\Persistence;

interface PagePersistenceInterface
{
    /**
     * @param string $id
     * @return array
     */
    public function findById(string $id): array;

    /**
     * @return array
     */
    public function findAll(): array;
}
