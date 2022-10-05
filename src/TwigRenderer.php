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

    private array $config;

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
        $this->config = $config->toArray();
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
        if (!empty($this->config['twig']['cache'])) {
            $cachePath = $this->config['paths']['site'] . '/runtime/cache/twig';
            if (!is_dir($cachePath)) {
                throw SystemException::directoryNotExist($cachePath);
            }
            $cache = $cachePath;
        }

        // see \Twig\Environment default options
        $twigOptions = [
            'autoescape'       => $this->config['twig']['autoescape'] ?? 'html',
            'cache'            => $cache,
            'charset'          => $this->config['twig']['charset'] ?? 'UTF-8',
            'debug'            => $this->config['twig']['debug'] ?? false,
            'strict_variables' => $this->config['twig']['strictVariables'] ?? false,
        ];

        $this->twig = new TwigEnvironment($loader, $twigOptions);

        if (!empty($this->config['twig']['debug'])) {
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
            'theme' => $this->config['theme'],
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

    public function addTest(TwigTest $test): void
    {
        $this->twig->addTest($test);
    }

    /**
     * @throws LoaderError
     */
    private function getTwigFilesystemLoader(): ChainLoader
    {
        $paths = [];
        if (empty($this->config['theme'])) {
            $paths[] = $this->config['paths']['themes'];
        } elseif ($this->config['theme'] === 'default') {
            $paths[] = $this->config['paths']['themes'] . '/default';
        } else {
            $paths[] = $this->config['paths']['themes'] . '/' . $this->config['theme'];
        }

        $paths = $this->validatePaths($paths);

        $loader1 = new TwigStringLoader();
        $loader2 = new FilesystemLoader($paths);

        // namespaces
        $namespaces = [
            'plugin' => $this->config['paths']['plugins'],
            'page' => $this->config['paths']['pages'],
            'site' => $this->config['paths']['site'],
            'snippet' => $this->config['paths']['app'] . '/templates/snippets',
            'sysplugin' => $this->config['paths']['sysPlugins'],
            'template' => $this->config['paths']['app'] . '/templates',
            'vendor' => $this->config['paths']['app'] . '/vendor',
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
