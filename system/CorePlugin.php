<?php

declare(strict_types=1);

namespace herbie;

use herbie\commands\ClearFilesCommand;
use herbie\events\RenderLayoutEvent;
use herbie\events\RenderPageEvent;
use herbie\events\RenderSegmentEvent;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class CorePlugin extends Plugin
{
    private TwigRenderer $twigRenderer;
    private string $layoutFileExtension;
    private bool $enableTwigInLayoutFilter;
    private bool $enableTwigInSegmentFilter;

    public function __construct(Config $config, TwigRenderer $twigRenderer)
    {
        $this->enableTwigInLayoutFilter = $config->getAsBool('plugins.CORE.enableTwigInLayoutFilter');
        $this->enableTwigInSegmentFilter = $config->getAsBool('plugins.CORE.enableTwigInSegmentFilter');
        $this->layoutFileExtension = trim($config->getAsString('fileExtensions.layouts'));
        $this->twigRenderer = $twigRenderer;
    }

    public function consoleCommands(): array
    {
        return [
            ClearFilesCommand::class
        ];
    }

    public function eventListeners(): array
    {
        return [
            [RenderLayoutEvent::class, [$this, 'onRenderLayout']],
            [RenderPageEvent::class, [$this, 'onRenderPage']],
            [RenderSegmentEvent::class, [$this, 'onRenderSegment']]
        ];
    }

    public function twigFunctions(): array
    {
        return [
            ['herbie_debug', [$this, 'herbieDebug']],
        ];
    }

    public function twigGlobals(): array
    {
        return [];
    }

    public function herbieDebug(): bool
    {
        return Application::isDebug();
    }

    public function onRenderPage(RenderPageEvent $event): void
    {
        $twig = $this->twigRenderer->getTwigEnvironment();
        $twig->addGlobal('page', $event->getPage());
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function onRenderSegment(RenderSegmentEvent $event): void
    {
        if (!$this->enableTwigInSegmentFilter || !$event->getPage()->getTwig()) {
            return;
        }
        $segment = $event->getSegment();
        $renderedSegment = $this->twigRenderer->renderString($segment);
        $event->setSegment($renderedSegment);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function onRenderLayout(RenderLayoutEvent $event): void
    {
        if ($this->layoutFileExtension !== '') {
            $templateName = sprintf('%s.%s', $event->getLayout(), $this->layoutFileExtension);
        } else {
            $templateName = $event->getLayout();
        }

        if ($this->enableTwigInLayoutFilter) {
            $context = ['content' => $event->getSegments()];
            $content = $this->twigRenderer->renderTemplate($templateName, $context);
        } else {
            $content = join('', $event->getSegments());
        }

        // The rendered content, that must be used in next listeners.
        $event->setContent($content);

        // Unset segments to make it obvious for the next listener.
        $event->unsetSegments();
    }
}
