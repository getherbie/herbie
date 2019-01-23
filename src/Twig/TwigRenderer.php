<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-01-06
 * Time: 08:52
 */

declare(strict_types=1);

namespace Herbie\Twig;

use Herbie\Config;
use Herbie\Environment;
use Herbie\EventManager;
use Herbie\Exception\SystemException;
use Herbie\Site;
use Twig_Environment;
use Twig_Error_Loader;
use Twig_Extension_Debug;
use Twig_Filter;
use Twig_Function;
use Twig_Loader_Chain as Twig_Loader_Chain;
use Twig_Loader_Filesystem;
use Twig_Test;

class TwigRenderer
{
    /**
     * @var bool
     */
    private $initialized;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var TwigExtension
     */
    private $twigExtension;

    /**
     * @var Site
     */
    private $site;

    /**
     * TwigRenderer constructor.
     * @param Config $config
     * @param Environment $environment
     * @param EventManager $eventManager
     * @param Site $site
     * @param TwigExtension $twigExtension
     */
    public function __construct(
        Config $config,
        Environment $environment,
        EventManager $eventManager,
        Site $site,
        TwigExtension $twigExtension
    ) {
        $this->initialized = false;
        $this->environment = $environment;
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->twigExtension = $twigExtension;
        $this->site = $site;
    }

    /**
     * @throws Twig_Error_Loader
     * @throws \Throwable
     */
    public function init(): void
    {
        $loader = $this->getTwigFilesystemLoader();

        $cache = false;
        if (!empty($this->config['twig']['cache'])) {
            $cachePath = $this->config['paths']['site'] . '/runtime/cache/twig';
            if (!is_dir($cachePath)) {
                throw SystemException::directoryNotExist($cachePath);
            }
            $cache = $cachePath;
        }

        $this->twig = new Twig_Environment($loader, [
            'debug' => $this->config['twig']['debug'],
            'cache' => $cache
        ]);

        if (!empty($this->config['twig']['debug'])) {
            $this->twig->addExtension(new Twig_Extension_Debug());
        }

        $this->twigExtension->setTwigRenderer($this);
        $this->twig->addExtension($this->twigExtension);

        $this->addTwigPlugins();

        /*
        foreach (Hook::trigger(Hook::CONFIG, 'addTwigFunction') as $function) {
            try {
                list($name, $callable, $options) = $function;
                $this->twig->addFunction(new \Twig_SimpleFunction($name, $callable, (array)$options));
            } catch (\Exception $e) {
                ; //do nothing else yet
            }
        }

        foreach (Hook::trigger(Hook::CONFIG, 'addTwigFilter') as $filter) {
            try {
                list($name, $callable, $options) = $filter;
                $this->twig->addFilter(new \Twig_SimpleFilter($name, $callable, (array)$options));
            } catch (\Exception $e) {
                ; //do nothing else yet
            }
        }

        foreach (Hook::trigger(Hook::CONFIG, 'addTwigTest') as $test) {
            try {
                list($name, $callable, $options) = $test;
                $this->twig->addTest(new \Twig_SimpleTest($name, $callable, (array)$options));
            } catch (\Exception $e) {
                ; //do nothing else yet
            }
        }
        */
        $this->initialized = true;
        $this->eventManager->trigger('onTwigInitialized', $this);
    }

    /**
     * @param string $string
     * @param array $context
     * @return string
     * @throws \Throwable
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
     * @throws Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
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
     * @param Twig_Function $function
     */
    public function addFunction(Twig_Function $function): void
    {
        $this->twig->addFunction($function);
    }

    /**
     * @param Twig_Filter $filter
     */
    public function addFilter(Twig_Filter $filter): void
    {
        $this->twig->addFilter($filter);
    }

    /**
     * @param Twig_Test $test
     */
    public function addTest(Twig_Test $test): void
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
        foreach ($this->readPhpFiles($dir) as $file) {
            $included = $this->includePhpFile($file);
            $this->twig->addFunction($included);
        }
        // Filters
        $dir = $this->config['twig']['filtersPath'];
        foreach ($this->readPhpFiles($dir) as $file) {
            $included = $this->includePhpFile($file);
            $this->twig->addFilter($included);
        }
        // Tests
        $dir = $this->config['twig']['testsPath'];
        foreach ($this->readPhpFiles($dir) as $file) {
            $included = $this->includePhpFile($file);
            $this->twig->addTest($included);
        }
    }

    /**
     * @throws Twig_Loader_Chain
     * @throws Twig_Error_Loader
     */
    private function getTwigFilesystemLoader(): Twig_Loader_Chain
    {
        $paths = [];
        if (empty($this->config['theme'])) {
            $paths[] = $this->config['paths']['layouts'];
        } elseif ($this->config['theme'] === 'default') {
            $paths[] = $this->config['paths']['layouts'] . '/default';
        } else {
            $paths[] = $this->config['paths']['layouts'] . '/' . $this->config['theme'];
            $paths[] = $this->config['paths']['layouts'] . '/default';
        }

        $loader1 = new TwigStringLoader();
        $loader2 = new Twig_Loader_Filesystem($paths);

        // namespaces
        $namespaces = [
            'plugin' => $this->config['paths']['plugins'],
            'page' => $this->config['paths']['pages'],
            'site' => $this->config['paths']['site'],
            'snippet' => $this->config['paths']['app'] . '/../templates/snippets'
        ];
        foreach ($namespaces as $namespace => $path) {
            if (is_readable($path)) {
                $loader2->addPath($path, $namespace);
            }
        }

        $loader = new Twig_Loader_Chain([$loader1, $loader2]);
        return $loader;
    }

    /**
     * @param string $file
     * @return string
     */
    private function includePhpFile(string $file): string
    {
        return include($file);
    }

    /**
     * @param string $dir
     * @return array
     */
    private function readPhpFiles(string $dir): array
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
