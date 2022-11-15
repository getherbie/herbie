<?php

declare(strict_types=1);

namespace herbie\sysplugin\twig_plus;

use herbie\Environment;
use herbie\event\TwigInitializedEvent;
use herbie\PageRepositoryInterface;
use herbie\Plugin;
use herbie\TwigRenderer;
use herbie\UrlManager;

final class TwigPlusPlugin extends Plugin
{
    private Environment $environment;
    private PageRepositoryInterface $pageRepository;
    private TwigRenderer $twigRenderer;
    private UrlManager $urlManager;

    public function __construct(
        Environment $environment,
        PageRepositoryInterface $pageRepository,
        TwigRenderer $twigRenderer,
        UrlManager $urlManager
    ) {
        $this->environment = $environment;
        $this->pageRepository = $pageRepository;
        $this->twigRenderer = $twigRenderer;
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
        $event->getTwigRenderer()
            ->getTwigEnvironment()
            ->addExtension(new TwigPlusExtension(
                $this->environment,
                $this->pageRepository,
                $this->twigRenderer,
                $this->urlManager
            ));
    }
}
