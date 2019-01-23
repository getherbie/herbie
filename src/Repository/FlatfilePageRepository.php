<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 31.12.18
 * Time: 15:16
 */

declare(strict_types=1);

namespace Herbie\Repository;

use Herbie\Environment;
use Herbie\Page\Page;
use Herbie\Page\PageFactory;
use Herbie\Page\PageList;
use Herbie\Page\PageTrail;
use Herbie\Page\PageTree;
use Herbie\Persistence\PagePersistenceInterface;

class FlatfilePageRepository implements PageRepositoryInterface
{
    /**
     * @var Environment
     */
    private $environment;

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
     * @param Environment $environment
     * @param PageFactory $pageFactory
     * @param PagePersistenceInterface $pagePersistence
     */
    public function __construct(Environment $environment, PageFactory $pageFactory, PagePersistenceInterface $pagePersistence)
    {
        $this->environment = $environment;
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
     * @return PageTree
     */
    public function buildTree(): PageTree
    {
        return $this->pageFactory->newPageTree(
            $this->findAll()
        );
    }

    /**
     * @return PageTrail
     */
    public function buildTrail(): PageTrail
    {
        return $this->pageFactory->newPageTrail(
            $this->findAll(),
            $this->environment
        );
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
