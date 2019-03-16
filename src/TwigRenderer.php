<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

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

class TwigRenderer
{
    /**
     * @var bool
     */
    private $initialized;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var TwigCoreExtension
     */
    private $twigCoreExtension;

    /**
     * @var Site
     */
    private $site;

    /**
     * @var TwigPlusExtension
     */
    private $twigPlusExtension;

    /**
     * TwigRenderer constructor.
     * @param Configuration $config
     * @param Environment $environment
     * @param EventManager $eventManager
     * @param Site $site
     * @param TwigCoreExtension $twigExtension
     * @param TwigPlusExtension $twigPlusExtension
     */
    public function __construct(
        Configuration $config,
        Environment $environment,
        EventManager $eventManager,
        Site $site,
        TwigCoreExtension $twigExtension,
        TwigPlusExtension $twigPlusExtension
    ) {
        $this->initialized = false;
        $this->environment = $environment;
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->twigCoreExtension = $twigExtension;
        $this->site = $site;
        $this->twigPlusExtension = $twigPlusExtension;
    }

    /**
     * @throws LoaderError
     * @throws \Throwable
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

        $this->twig = new TwigEnvironment($loader, [
            'debug' => $this->config['twig']['debug'],
            'cache' => $cache
        ]);

        if (!empty($this->config['twig']['debug'])) {
            $this->twig->addExtension(new DebugExtension());
        }

        $this->twigCoreExtension->setTwigRenderer($this);
        $this->twig->addExtension($this->twigCoreExtension);

        $this->twigPlusExtension->setTwigRenderer($this);
        $this->twig->addExtension($this->twigPlusExtension);

        $this->addTwigPlugins();

        $this->initialized = true;
        $this->eventManager->trigger('onTwigInitialized', $this);
    }

    /**
     * @param string $string
     * @param array $context
     * @return string
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
     * @param string $name
     * @param array $context
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderTemplate(string $name, array $context = []): string
    {
        $context = array_merge($this->getContext(), $context);
        return $this->twig->render($name, $context);
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return [
            'route' => $this->environment->getRoute(),
            'routeParams' => [], // will be set by page renderer middleware
            'baseUrl' => $this->environment->getBaseUrl(),
            'theme' => $this->config['theme'],
            'site' => $this->site,
            'page' => null, // will be set by page renderer middleware
            'config' => $this->config->toArray()
        ];
    }

    /**
     * @param TwigFunction $function
     */
    public function addFunction(TwigFunction $function): void
    {
        $this->twig->addFunction($function);
    }

    /**
     * @param TwigFilter $filter
     */
    public function addFilter(TwigFilter $filter): void
    {
        $this->twig->addFilter($filter);
    }

    /**
     * @param TwigTest $test
     */
    public function addTest(TwigTest $test): void
    {
        $this->twig->addTest($test);
    }

    /**
     * @return void
     */
    private function addTwigPlugins(): void
    {
        // Functions
        $dir = $this->config['twig']['functionsPath'];
        foreach ($this->globPhpFiles($dir) as $file) {
            /** @var TwigFunction $twigFunction */
            $twigFunction = $this->includePhpFile($file);
            $this->twig->addFunction($twigFunction);
        }
        // Filters
        $dir = $this->config['twig']['filtersPath'];
        foreach ($this->globPhpFiles($dir) as $file) {
            /** @var TwigFilter $twigFilter */
            $twigFilter = $this->includePhpFile($file);
            $this->twig->addFilter($twigFilter);
        }
        // Tests
        $dir = $this->config['twig']['testsPath'];
        foreach ($this->globPhpFiles($dir) as $file) {
            /** @var TwigTest $twigTest */
            $twigTest = $this->includePhpFile($file);
            $this->twig->addTest($twigTest);
        }
    }

    /**
     * @throws ChainLoader
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

        $loader1 = new TwigStringLoader();
        $loader2 = new FilesystemLoader($paths);

        // namespaces
        $namespaces = [
            'plugin' => $this->config['paths']['plugins'],
            'page' => $this->config['paths']['pages'],
            'site' => $this->config['paths']['site'],
            'snippet' => $this->config['paths']['app'] . '/templates/snippets',
            'sysplugin' => $this->config['paths']['sysPlugins'],
            'template' => $this->config['paths']['app'] . '/templates'
        ];
        foreach ($namespaces as $namespace => $path) {
            if (is_readable($path)) {
                $loader2->addPath($path, $namespace);
            }
        }

        $loader = new ChainLoader([$loader1, $loader2]);
        return $loader;
    }

    /**
     * @param string $file
     * @return object
     */
    private function includePhpFile(string $file): object
    {
        return include($file);
    }

    /**
     * @param string $dir
     * @return array
     */
    private function globPhpFiles(string $dir): array
    {
        $dir = rtrim($dir, '/');
        if (empty($dir) || !is_readable($dir)) {
            return [];
        }
        $pattern = $dir . '/*.php';
        return glob($pattern);
    }

    /**
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }
}
