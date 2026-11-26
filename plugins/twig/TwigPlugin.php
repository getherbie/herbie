<?php

declare(strict_types=1);

namespace herbie\plugins\twig;

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
        private readonly Alias $alias,
        private readonly Assets $assets,
        private readonly Config $config,
        private readonly PageRepositoryInterface $pageRepository,
        private readonly SlugGenerator $slugGenerator,
        private readonly Translator $translator,
        private readonly UrlManager $urlManager,
    ) {}

    #[\Override]
    public function eventListeners(): array
    {
        return [
            [TwigInitializedEvent::class, $this->onTwigInitialized(...)],
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
