<?php

declare(strict_types=1);

namespace herbie;

use herbie\events\TwigInitializedEvent;
use Psr\Log\LoggerInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class TwigRenderer
{
    private bool $initialized = false;
    private TwigEnvironment $twig;

    /**
     * TwigRenderer constructor.
     */
    public function __construct(
        private readonly Config $config,
        private readonly EventManager $eventManager,
        private readonly LoggerInterface $logger,
        private readonly Site $site,
    ) {}

    /**
     * @throws LoaderError
     * @throws SystemException
     */
    public function init(): void
    {
        if ($this->isInitialized()) {
            return;
        }

        $loader = $this->getTwigFilesystemLoader();

        $cache = false;
        if ($this->config->getAsBool('components.twigRenderer.cache')) {
            $cachePath = $this->config->getAsString('paths.site') . '/runtime/cache/twig';
            if (!is_dir($cachePath)) {
                throw SystemException::directoryNotExist($cachePath);
            }
            $cache = $cachePath;
        }

        // see \Twig\Environment default options
        $twigOptions = [
            'autoescape' => $this->config->getAsString('components.twigRenderer.autoescape', 'html'),
            'cache' => $cache,
            'charset' => $this->config->getAsString('components.twigRenderer.charset', 'UTF-8'),
            'debug' => $this->config->getAsBool('components.twigRenderer.debug'),
            'strict_variables' => $this->config->getAsBool('components.twigRenderer.strictVariables'),
        ];

        $this->twig = new TwigEnvironment($loader, $twigOptions);

        if ($this->config->getAsBool('components.twigRenderer.debug')) {
            $this->twig->addExtension(new DebugExtension());
        }

        $this->twig->addGlobal('page', null); // will be set by page renderer middleware
        $this->twig->addGlobal('site', $this->site);

        $this->initialized = true;

        $this->eventManager->dispatch(new TwigInitializedEvent($this->twig));
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * @throws LoaderError
     */
    private function getTwigFilesystemLoader(): ChainLoader
    {
        $theme = trim($this->config->getAsString('theme'));
        $themePath = trim($this->config->getAsString('paths.themes'));

        $paths = [];
        if ($theme === '') {
            $paths[] = $themePath;
        } else {
            $paths[] = $themePath . '/' . $theme;
        }

        $paths = $this->validatePaths($paths);

        $loader1 = new TwigStringLoader();
        $loader2 = new FilesystemLoader($paths);

        // namespaces
        // the user plugin path comes first, so user templates win; the plugin path
        // of Herbie itself (system plugins) is appended at the same namespace
        $namespaces = [
            'plugin' => $this->config->getAsString('paths.plugins'),
            'page' => $this->config->getAsString('paths.pages'),
            'site' => $this->config->getAsString('paths.site'),
            'snippet' => Application::getHerbiePath('/templates/snippets'),
            'template' => Application::getHerbiePath('/templates'),
            'vendor' => $this->config->getAsString('paths.app') . '/vendor',
        ];

        foreach ($namespaces as $namespace => $path) {
            if (is_readable($path)) {
                $loader2->addPath($path, $namespace);
            }
        }

        return new ChainLoader([$loader1, $loader2]);
    }

    private function validatePaths(array $paths): array
    {
        foreach ($paths as $i => $path) {
            if (!is_dir($path)) {
                $this->logger->error(sprintf('Directory "%s" does not exist', $path));
                // we remove not existing paths here because Twig's loader would throw an error
                unset($paths[$i]);
            }
        }
        return array_values($paths);
    }

    /**
     * @param mixed $mixed
     */
    public function addGlobal(string $name, $mixed): void
    {
        $this->twig->addGlobal($name, $mixed);
    }

    public function getTwigEnvironment(): TwigEnvironment
    {
        return $this->twig;
    }

    /**
     * @param array<string, mixed> $context
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderString(string $string, array $context = []): string
    {
        return $this->twig->render($string, $context);
    }

    /**
     * @param array<string, mixed> $context
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderTemplate(string $name, array $context = []): string
    {
        return $this->twig->render($name, $context);
    }

    public function addFunction(TwigFunction $function): void
    {
        $this->twig->addFunction($function);
    }

    public function addFilter(TwigFilter $filter): void
    {
        $this->twig->addFilter($filter);
    }

    public function addTest(TwigTest $test): void
    {
        $this->twig->addTest($test);
    }
}
