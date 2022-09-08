<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

class FlatfilePageRepository implements PageRepositoryInterface
{
    private PageFactory $pageFactory;
    private ?PageList $pageList;
    private PagePersistenceInterface $pagePersistence;

    /**
     * FlatfilePageRepository constructor.
     */
    public function __construct(PageFactory $pageFactory, PagePersistenceInterface $pagePersistence)
    {
        $this->pageFactory = $pageFactory;
        $this->pageList = null;
        $this->pagePersistence = $pagePersistence;
    }

    /**
     * @param string $id The aliased unique path to the file (i.e. @page/about/company.md)
     */
    public function find(string $id): ?Page
    {
        $data = $this->pagePersistence->findById($id);
        return $this->createPage($data);
    }

    public function findAll(): PageList
    {
        if (is_null($this->pageList)) {
            $this->pageList = $this->pageFactory->newPageList();
            foreach ($this->pagePersistence->findAll() as $id => $data) {
                $pageItem = $this->pageFactory->newPageItem($data['data']);
                $this->pageList->addItem($pageItem);
            }
        }
        return $this->pageList;
    }

    public function save(Page $page): bool
    {
        // TODO: Implement save() method.
        return false;
    }

    public function delete(Page $page): bool
    {
        // TODO: Implement remove() method.
        return false;
    }

    private function createPage(array $data): Page
    {
        return $this->pageFactory->newPage(
            $data['id'],
            $data['parent'],
            $data['data'],
            $data['segments']
        );
    }
}
