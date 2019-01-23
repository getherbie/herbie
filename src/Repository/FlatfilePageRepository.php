<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 31.12.18
 * Time: 15:16
 */

declare(strict_types=1);

namespace Herbie\Repository;

use Herbie\Cache;
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
     * @var PagePersistenceInterface
     */
    private $pagePersistence;

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var PageList
     */
    private $pageList;
    /**
     * @var Environment
     */
    private $environment;

    /**
     * FlatfilePageRepository constructor.
     * @param PagePersistenceInterface $pagePersistence
     * @param PageFactory $pageFactory
     * @param Cache $cache
     * @param Environment $environment
     */
    public function __construct(PagePersistenceInterface $pagePersistence, PageFactory $pageFactory, Cache $cache, Environment $environment)
    {
        $this->pagePersistence = $pagePersistence;
        $this->pageFactory = $pageFactory;
        $this->cache = $cache;
        $this->pageList = null;
        $this->environment = $environment;
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
