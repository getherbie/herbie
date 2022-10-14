<?php

declare(strict_types=1);

namespace herbie;

use Psr\Log\LoggerInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;
use Twig\Environment as TwigEnvironment;

final class TwigRenderer
{
    private bool $initialized;

    private Config $config;

    private Environment $environment;

    private TwigEnvironment $twig;

    private EventManager $eventManager;

    private LoggerInterface $logger;

    private Site $site;

    /**
     * TwigRenderer constructor.
     */
    public function __construct(
        Config $config,
        Environment $environment,
        EventManager $eventManager,
        LoggerInterface $logger,
        Site $site
    ) {
        $this->initialized = false;
        $this->environment = $environment;
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->site = $site;
    }

    /**
     * @throws LoaderError
     * @throws SystemException
     */
    public function init(): void
    {
        // initialize only once
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
            'autoescape'       => $this->config->getAsString('components.twigRenderer.autoescape', 'html'),
            'cache'            => $cache,
            'charset'          => $this->config->getAsString('components.twigRenderer.charset', 'UTF-8'),
            'debug'            => $this->config->getAsBool('components.twigRenderer.debug'),
            'strict_variables' => $this->config->getAsBool('components.twigRenderer.strictVariables'),
        ];

        $this->twig = new TwigEnvironment($loader, $twigOptions);

        if ($this->config->getAsBool('components.twigRenderer.debug')) {
            $this->twig->addExtension(new DebugExtension());
        }

        $this->eventManager->trigger('onTwigAddExtension', $this);

        $this->initialized = true;
        $this->eventManager->trigger('onTwigInitialized', $this);
    }

    public function getTwigEnvironment(): TwigEnvironment
    {
        return $this->twig;
    }

    public function getFilters(): array
    {
        $items = [];
        foreach ($this->getTwigEnvironment()->getFilters() as $f) {
            $items[] = [
                $f->getName(),
                get_callable_name($f->getCallable())
            ];
        }
        return $items;
    }

    public function getFunctions(): array
    {
        $items = [];
        foreach ($this->getTwigEnvironment()->getFunctions() as $f) {
            $items[] = [
                $f->getName(),
                get_callable_name($f->getCallable())
            ];
        }
        return $items;
    }

    public function getTests(): array
    {
        $items = [];
        foreach ($this->getTwigEnvironment()->getTests() as $f) {
            $callable = $f->getCallable() ?? $f->getName();
            $items[] = [
                $f->getName(),
                get_callable_name($callable)
            ];
        }
        return $items;
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderString(string $string, array $context = []): string
    {
        $context = array_merge($this->getContext(), $context);
        return $this->twig->render($string, $context);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderTemplate(string $name, array $context = []): string
    {
        $context = array_merge($this->getContext(), $context);
        return $this->twig->render($name, $context);
    }

    public function getContext(): array
    {
        return [
            'route' => $this->environment->getRoute(),
            'routeParams' => [], // will be set by page renderer middleware
            'baseUrl' => $this->environment->getBaseUrl(),
            'theme' => $this->config->getAsString('theme'),
            'site' => $this->site,
            'page' => null, // will be set by page renderer middleware
            'config' => $this->config
        ];
    }

    public function addFunction(TwigFunction $function): void
    {
        $this->twig->addFunction($function);
    }

    public function addFilter(TwigFilter $filter): void
    {
        $this->twig->addFilter($filter);
    }

    public function addGlobal(string $name, $mixed): void
    {
        $this->twig->addGlobal($name, $mixed);
    }

    public function addTest(TwigTest $test): void
    {
        $this->twig->addTest($test);
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
        $namespaces = [
            'plugin' => $this->config->getAsString('paths.plugins'),
            'page' => $this->config->getAsString('paths.pages'),
            'site' => $this->config->getAsString('paths.site'),
            'snippet' => Application::getHerbiePath('/templates/snippets'),
            'sysplugin' => Application::getHerbiePath('/sysplugins'),
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

    public function isInitialized(): bool
    {
        return $this->initialized;
    }
}
