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
     * @var PageList
     */
    private $pageList;

    /**
     * @var PageTree
     */
    private $pageTree;

    /**
     * @var PageTrail
     */
    private $pageTrail;

    /**
     * Site constructor.
     * @param Config $config
     * @param DataRepositoryInterface $dataRepository
     * @param PageList $pageList
     * @param PageTree $pageTree
     * @param PageTrail $pageTrail
     */
    public function __construct(
        Config $config,
        DataRepositoryInterface $dataRepository,
        PageList $pageList,
        PageTree $pageTree,
        PageTrail $pageTrail
    ) {
        $this->config = $config;
        $this->dataRepository = $dataRepository;
        $this->pageList = $pageList;
        $this->pageTree = $pageTree;
        $this->pageTrail = $pageTrail;
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
        return $this->pageList;
    }

    /**
     * @return PageTree
     */
    public function getPageTree(): PageTree
    {
        return $this->pageTree;
    }

    /**
     * @return PageTrail
     */
    public function getPageTrail(): PageTrail
    {
        return $this->pageTrail;
    }

    /**
     * @return string
     */
    public function getModified(): string
    {
        $lastModified = 0;
        foreach ($this->pageList as $item) {
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
