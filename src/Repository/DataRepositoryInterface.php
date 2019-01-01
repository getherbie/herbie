<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 01.01.19
 * Time: 14:31
 */

namespace Herbie\Repository;

interface DataRepositoryInterface
{
    /**
     * @param string $name
     * @return array
     */
    public function find(string $name): array;

    /**
     * @return array
     */
    public function findAll(): array;

}
