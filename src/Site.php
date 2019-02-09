<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

/**
 * Stores the site.
 */
class Site
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var DataRepositoryInterface
     */
    private $dataRepository;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * Site constructor.
     * @param Configuration $config
     * @param DataRepositoryInterface $dataRepository
     * @param Environment $environment
     * @param PageRepositoryInterface $pageRepository
     */
    public function __construct(
        Configuration $config,
        DataRepositoryInterface $dataRepository,
        Environment $environment,
        PageRepositoryInterface $pageRepository
    ) {
        $this->config = $config;
        $this->dataRepository = $dataRepository;
        $this->pageRepository = $pageRepository;
        $this->environment = $environment;
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
        return $this->getPageList()->getPageTree();
    }

    /**
     * @return PageTrail
     */
    public function getPageTrail(): PageTrail
    {
        $route = $this->environment->getRoute();
        return $this->getPageList()->getPageTrail($route);
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
