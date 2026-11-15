<?php

declare(strict_types=1);

namespace herbie\sysplugins\twig;

use Ausi\SlugGenerator\SlugGenerator;
use herbie\AbstractPlugin;
use herbie\Alias;
use herbie\Assets;
use herbie\Config;
use herbie\events\TwigInitializedEvent;
use herbie\PageRepositoryInterface;
use herbie\Plugin;
use herbie\Translator;
use herbie\UrlManager;

#[Plugin('twig')]
final class TwigPlugin extends AbstractPlugin
{
    public function __construct(
        private Alias $alias,
        private Assets $assets,
        private Config $config,
        private PageRepositoryInterface $pageRepository,
        private SlugGenerator $slugGenerator,
        private Translator $translator,
        private UrlManager $urlManager,
    ) {}

    public function eventListeners(): array
    {
        return [
            [TwigInitializedEvent::class, [$this, 'onTwigInitialized']],
        ];
    }

    public function onTwigInitialized(TwigInitializedEvent $event): void
    {
        $event->getEnvironment()->addExtension(
            new TwigExtension(
                $this->alias,
                $this->assets,
                $this->config,
                $event->getEnvironment(),
                $this->pageRepository,
                $this->slugGenerator,
                $this->translator,
                $this->urlManager,
            ),
        );
    }
}
