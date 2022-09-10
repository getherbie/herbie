<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

/**
 * Stores the site.
 */
final class Site
{
    private Config $config;

    private DataRepositoryInterface $dataRepository;

    private Environment $environment;

    private PageRepositoryInterface $pageRepository;

    /**
     * Site constructor.
     */
    public function __construct(
        Config $config,
        DataRepositoryInterface $dataRepository,
        Environment $environment,
        PageRepositoryInterface $pageRepository
    ) {
        $this->config = $config;
        $this->dataRepository = $dataRepository;
        $this->pageRepository = $pageRepository;
        $this->environment = $environment;
    }

    public function getTime(): string
    {
        return date('c');
    }

    public function getData(): array
    {
        return $this->dataRepository->loadAll();
    }

    public function getPageList(): PageList
    {
        return $this->pageRepository->findAll();
    }

    public function getPageTree(): PageTree
    {
        return $this->getPageList()->getPageTree();
    }

    public function getPageTrail(): PageTrail
    {
        $route = $this->environment->getRoute();
        return $this->getPageList()->getPageTrail($route);
    }

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

    public function getLanguage(): string
    {
        return $this->config->get('language');
    }

    public function getLocale(): string
    {
        return $this->config->get('locale');
    }

    public function getCharset(): string
    {
        return $this->config->get('charset');
    }
}
