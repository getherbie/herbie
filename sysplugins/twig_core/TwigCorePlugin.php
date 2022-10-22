<?php

declare(strict_types=1);

namespace herbie\sysplugin\twig_core;

use Ausi\SlugGenerator\SlugGenerator;
use herbie\Alias;
use herbie\Assets;
use herbie\Environment;
use herbie\EventInterface;
use herbie\Plugin;
use herbie\Translator;
use herbie\TwigRenderer;
use herbie\UrlGenerator;
use Psr\Log\LoggerInterface;

final class TwigCorePlugin extends Plugin
{
    private Alias $alias;
    private Assets $assets;
    private Environment $environment;
    private SlugGenerator $slugGenerator;
    private Translator $translator;
    private UrlGenerator $urlGenerator;

    public function __construct(
        Alias $alias,
        Assets $assets,
        Environment $environment,
        SlugGenerator $slugGenerator,
        Translator $translator,
        UrlGenerator $urlGenerator
    ) {
        $this->alias = $alias;
        $this->assets = $assets;
        $this->environment = $environment;
        $this->slugGenerator = $slugGenerator;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
    }

    public function events(): array
    {
        return [
            ['onTwigAddExtension', [$this, 'onTwigAddExtension']],
        ];
    }

    public function onTwigAddExtension(EventInterface $event): void
    {
        /** @var TwigRenderer $twigRenderer */
        $twigRenderer = $event->getTarget();
        $twigRenderer->getTwigEnvironment()->addExtension(new TwigCoreExtension(
            $this->alias,
            $this->assets,
            $this->environment,
            $this->slugGenerator,
            $this->translator,
            $twigRenderer,
            $this->urlGenerator
        ));
    }
}
