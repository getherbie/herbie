<?php

declare(strict_types=1);

namespace herbie\sysplugin\twig_plus;

use herbie\event\TwigInitializedEvent;
use herbie\PageRepositoryInterface;
use herbie\Plugin;
use herbie\UrlManager;

final class TwigPlusPlugin extends Plugin
{
    private PageRepositoryInterface $pageRepository;
    private UrlManager $urlManager;

    public function __construct(
        PageRepositoryInterface $pageRepository,
        UrlManager $urlManager
    ) {
        $this->pageRepository = $pageRepository;
        $this->urlManager = $urlManager;
    }

    public function eventListeners(): array
    {
        return [
            [TwigInitializedEvent::class, [$this, 'onTwigInitialized']],
        ];
    }

    public function onTwigInitialized(TwigInitializedEvent $event): void
    {
        $event->getEnvironment()->addExtension(new TwigPlusExtension(
            $event->getEnvironment(),
            $this->pageRepository,
            $this->urlManager
        ));
    }
}
