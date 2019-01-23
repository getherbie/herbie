<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

use Herbie\Page\PageList;
use Herbie\Page\PageTree;
use Herbie\Page\PageTrail;
use Herbie\Repository\DataRepositoryInterface;
use Herbie\Repository\PageRepositoryInterface;

/**
 * Stores the site.
 */
class Site
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var DataRepositoryInterface
     */
    private $dataRepository;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * Site constructor.
     * @param Config $config
     * @param DataRepositoryInterface $dataRepository
     * @param PageRepositoryInterface $pageRepository
     */
    public function __construct(
        Config $config,
        DataRepositoryInterface $dataRepository,
        PageRepositoryInterface $pageRepository
    ) {
        $this->config = $config;
        $this->dataRepository = $dataRepository;
        $this->pageRepository = $pageRepository;
    }

    /**
     * @return string
     */
    public function getTime(): string
    {
        return date('c');
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->dataRepository->loadAll();
    }

    /**
     * @return PageList
     */
    public function getPageList(): PageList
    {
        return $this->pageRepository->findAll();
    }

    /**
     * @return PageTree
     */
    public function getPageTree(): PageTree
    {
        return $this->pageRepository->buildTree();
    }

    /**
     * @return PageTrail
     */
    public function getPageTrail(): PageTrail
    {
        return $this->pageRepository->buildTrail();
    }

    /**
     * @return string
     */
    public function getModified(): string
    {
        $lastModified = 0;
        foreach ($this->pageRepository->findAll() as $item) {
            $modified = strtotime($item->getModified());
            if ($modified > $lastModified) {
                $lastModified = $modified;
            }
        }
        return date('c', $lastModified);
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->config['language'];
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->config['locale'];
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return $this->config['charset'];
    }
}
