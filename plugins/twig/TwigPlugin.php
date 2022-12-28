<?php

declare(strict_types=1);

namespace herbie\sysplugin\twig;

use Ausi\SlugGenerator\SlugGenerator;
use herbie\Alias;
use herbie\Assets;
use herbie\Config;
use herbie\events\TwigInitializedEvent;
use herbie\PageRepositoryInterface;
use herbie\Plugin;
use herbie\Translator;
use herbie\UrlManager;

final class TwigPlugin extends Plugin
{
    private Alias $alias;
    private Assets $assets;
    private Config $config;
    private PageRepositoryInterface $pageRepository;
    private SlugGenerator $slugGenerator;
    private Translator $translator;
    private UrlManager $urlManager;

    public function __construct(
        Alias $alias,
        Assets $assets,
        Config $config,
        PageRepositoryInterface $pageRepository,
        SlugGenerator $slugGenerator,
        Translator $translator,
        UrlManager $urlManager
    ) {
        $this->alias = $alias;
        $this->assets = $assets;
        $this->config = $config;
        $this->pageRepository = $pageRepository;
        $this->slugGenerator = $slugGenerator;
        $this->translator = $translator;
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
        $event->getEnvironment()->addExtension(new TwigExtension(
            $this->alias,
            $this->assets,
            $this->config,
            $event->getEnvironment(),
            $this->pageRepository,
            $this->slugGenerator,
            $this->translator,
            $this->urlManager
        ));
    }
}
