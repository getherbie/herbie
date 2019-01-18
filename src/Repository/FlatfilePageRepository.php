<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 31.12.18
 * Time: 15:16
 */

declare(strict_types=1);

namespace Herbie\Repository;

use Herbie\Page;
use Herbie\PageFactory;
use Herbie\Persistence\FlatfilePersistenceInterface;

class FlatfilePageRepository implements PageRepositoryInterface
{
    /**
     * @var FlatfilePersistenceInterface
     */
    private $pagePersistence;

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * FlatfilePageRepository constructor.
     * @param FlatfilePersistenceInterface $pagePersistence
     * @param PageFactory $pageFactory
     */
    public function __construct(FlatfilePersistenceInterface $pagePersistence, PageFactory $pageFactory)
    {
        $this->pagePersistence = $pagePersistence;
        $this->pageFactory = $pageFactory;
    }

    /**
     * @param string $id The aliased unique path to the file (i.e. @page/about/company.md)
     * @return Page|null
     * @throws \Exception
     */
    public function find(string $id): ?Page
    {
        $data = $this->pagePersistence->findById($id);
        $page = $this->createPage($data);
        return $page;
    }

    /**
     * @return array
     */
    public function findAll(): array
    {
        $pages = [];
        foreach ($this->pagePersistence->findAll() as $id => $row) {
            $pages[$id] = $this->createPage($row);
        }
        return $pages;
    }

    /**
     * @param Page $page
     * @return bool
     */
    public function save(Page $page): bool
    {
        // TODO: Implement save() method.
        return false;
    }

    /**
     * @param Page $page
     * @return bool
     */
    public function delete(Page $page): bool
    {
        // TODO: Implement remove() method.
        return false;
    }

    /**
     * @param array $data
     * @return Page
     */
    private function createPage(array $data): Page
    {
        return $this->pageFactory->newInstance(
            $data['id'],
            $data['parent'],
            $data['data'],
            $data['segments']
        );
    }
}
