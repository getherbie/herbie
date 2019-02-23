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
    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var PageList
     */
    private $pageList;

    /**
     * @var PagePersistenceInterface
     */
    private $pagePersistence;

    /**
     * FlatfilePageRepository constructor.
     * @param PageFactory $pageFactory
     * @param PagePersistenceInterface $pagePersistence
     */
    public function __construct(PageFactory $pageFactory, PagePersistenceInterface $pagePersistence)
    {
        $this->pageFactory = $pageFactory;
        $this->pageList = null;
        $this->pagePersistence = $pagePersistence;
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
     * @return PageList
     */
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
        return $this->pageFactory->newPage(
            $data['id'],
            $data['parent'],
            $data['data'],
            $data['segments']
        );
    }
}
