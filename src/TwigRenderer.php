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

final class TwigRenderer
{
    private bool $initialized;

    private array $config;

    private Environment $environment;

    private \Twig\Environment $twig;

    private EventManager $eventManager;

    private TwigCoreExtension $twigCoreExtension;

    private Site $site;

    private TwigPlusExtension $twigPlusExtension;

    /**
     * TwigRenderer constructor.
     */
    public function __construct(
        Config $config,
        Environment $environment,
        EventManager $eventManager,
        Site $site,
        TwigCoreExtension $twigExtension,
        TwigPlusExtension $twigPlusExtension
    ) {
        $this->initialized = false;
        $this->environment = $environment;
        $this->config = $config->toArray();
        $this->eventManager = $eventManager;
        $this->twigCoreExtension = $twigExtension;
        $this->site = $site;
        $this->twigPlusExtension = $twigPlusExtension;
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

    /**
     * @return mixed
     */
    private function includePhpFile(string $file)
    {
        return include($file);
    }

    private function globPhpFiles(string $dir): array
    {
        $dir = rtrim($dir, '/');
        if (empty($dir) || !is_readable($dir)) {
            return [];
        }
        $pattern = $dir . '/*.php';
        return glob($pattern);
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }
}
