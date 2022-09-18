<?php

declare(strict_types=1);

namespace herbie\sysplugin;

require_once __DIR__ . '/TwigPlusExtension.php';

use herbie\Environment;
use herbie\EventInterface;
use herbie\PageRepositoryInterface;
use herbie\Plugin;
use herbie\TwigRenderer;
use herbie\UrlGenerator;

final class TwigPlusPlugin extends Plugin
{
    private Environment $environment;
    private PageRepositoryInterface $pageRepository;
    private TwigRenderer $twigRenderer;
    private UrlGenerator $urlGenerator;

    public function __construct(Environment $environment, PageRepositoryInterface $pageRepository, TwigRenderer $twigRenderer, UrlGenerator $urlGenerator)
    {
        $this->environment = $environment;
        $this->pageRepository = $pageRepository;
        $this->twigRenderer = $twigRenderer;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return array[]
     */
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
        $twigRenderer->getTwigEnvironment()->addExtension(new TwigPlusExtension(
            $this->environment,
            $this->pageRepository,
            $this->twigRenderer,
            $this->urlGenerator
        ));
    }
}
