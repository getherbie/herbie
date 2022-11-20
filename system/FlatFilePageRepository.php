<?php

declare(strict_types=1);

namespace herbie;

final class FlatFilePageRepository implements PageRepositoryInterface
{
    private PageFactory $pageFactory;
    private ?MenuList $menuList;
    private PagePersistenceInterface $pagePersistence;

    /**
     * FlatfilePageRepository constructor.
     */
    public function __construct(PageFactory $pageFactory, PagePersistenceInterface $pagePersistence)
    {
        $this->pageFactory = $pageFactory;
        $this->menuList = null;
        $this->pagePersistence = $pagePersistence;
    }

    /**
     * @param string $id The aliased unique path to the file (i.e. @page/about/company.md)
     */
    public function getPage(string $id): ?Page
    {
        $data = $this->pagePersistence->findById($id);
        return $data ? $this->createPage($data) : null;
    }

    public function getMenuList(): MenuList
    {
        if ($this->menuList === null) {
            $this->menuList = $this->pageFactory->newMenuList();
            foreach ($this->pagePersistence->findAll() as $data) {
                $pageItem = $this->pageFactory->newMenuItem($data['data']);
                $this->menuList->addItem($pageItem);
            }
        }
        return $this->menuList;
    }

    public function savePage(Page $page): bool
    {
        // TODO: Implement save() method.
        return false;
    }

    public function deletePage(Page $page): bool
    {
        // TODO: Implement remove() method.
        return false;
    }

    private function createPage(array $data): Page
    {
        return $this->pageFactory->newPage(
            $data['data'],
            $data['segments']
        );
    }
}
