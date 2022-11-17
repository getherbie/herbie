<?php

declare(strict_types=1);

namespace herbie\sysplugin\twig_core;

use Ausi\SlugGenerator\SlugGenerator;
use herbie\Alias;
use herbie\Assets;
use herbie\event\TwigInitializedEvent;
use herbie\Plugin;
use herbie\Translator;
use herbie\UrlManager;

final class TwigCorePlugin extends Plugin
{
    private Alias $alias;
    private Assets $assets;
    private SlugGenerator $slugGenerator;
    private Translator $translator;
    private UrlManager $urlManager;

    public function __construct(
        Alias $alias,
        Assets $assets,
        SlugGenerator $slugGenerator,
        Translator $translator,
        UrlManager $urlManager
    ) {
        $this->alias = $alias;
        $this->assets = $assets;
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
        $event->getEnvironment()->addExtension(new TwigCoreExtension(
            $this->alias,
            $this->assets,
            $event->getEnvironment(),
            $this->slugGenerator,
            $this->translator,
            $this->urlManager
        ));
    }
}
