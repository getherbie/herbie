<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-01-06
 * Time: 08:52
 */

declare(strict_types=1);

namespace Herbie;

use Ausi\SlugGenerator\SlugGeneratorInterface;
use Herbie\Menu\MenuList;
use Herbie\Menu\MenuTree;
use Herbie\Menu\MenuTrail;
use Herbie\Repository\DataRepositoryInterface;
use Herbie\Url\UrlGenerator;
use Twig_Environment;
use Twig_Extension_Debug;
use Twig_Filter;
use Twig_Function;
use Twig_Loader_Array;
use Twig_Loader_Chain;
use Twig_Loader_Filesystem;
use Twig_Test;

class TwigRenderer
{
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
    private $alias;
    private $urlGenerator;
    private $translator;
    private $slugGenerator;
    private $assets;
    private $menuList;
    private $menuTree;
    private $menuTrail;
    private $dataRepository;
    private $eventManager;

    /**
     * TwigRenderer constructor.
     * @param Alias $alias
     * @param Config $config
     * @param UrlGenerator $urlGenerator
     * @param SlugGeneratorInterface $slugGenerator
     * @param Assets $assets
     * @param MenuList $menuList
     * @param MenuTree $menuTree
     * @param MenuTrail $menuTrail
     * @param Environment $environment
     * @param DataRepositoryInterface $dataRepository
     * @param Translator $translator
     * @param EventManager $eventManager
     */
    public function __construct(
        Alias $alias,
        Config $config,
        UrlGenerator $urlGenerator,
        SlugGeneratorInterface $slugGenerator,
        Assets $assets,
        MenuList $menuList,
        MenuTree $menuTree,
        MenuTrail $menuTrail,
        Environment $environment,
        DataRepositoryInterface $dataRepository,
        Translator $translator,
        EventManager $eventManager
    ) {
        $this->initialized = false;
        $this->environment = $environment;
        $this->alias = $alias;
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->slugGenerator = $slugGenerator;
        $this->assets = $assets;
        $this->menuList = $menuList;
        $this->menuTree = $menuTree;
        $this->menuTrail = $menuTrail;
        $this->dataRepository = $dataRepository;
        $this->eventManager = $eventManager;
    }

    /**
     * @throws \Twig_Error_Loader
     */
    public function init(): void
    {
        $loader = $this->getTwigFilesystemLoader();

        $this->twig = new Twig_Environment($loader, [
            'debug' => $this->config->twig->debug,
            'cache' => $this->config->twig->cache
        ]);

        if (!empty($this->config->twig->debug)) {
            $this->twig->addExtension(new Twig_Extension_Debug());
        }

        $herbieExtension = new TwigExtension(
            $this->alias,
            $this->config,
            $this->urlGenerator,
            $this->slugGenerator,
            $this->assets,
            $this->menuList,
            $this->menuTree,
            $this->menuTrail,
            $this->environment,
            $this->dataRepository,
            $this->translator,
            $this
        );

        $this->twig->addExtension($herbieExtension);

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

        $this->renderString('huhu test');
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
     * @throws \Twig_Error_Loader
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
    public function getContext()
    {
        return [
            'route' => $this->environment->getRoute(),
            'baseUrl' => $this->environment->getBaseUrl(),
            'theme' => $this->config->theme,
            'site' => new Site(
                $this->config,
                $this->dataRepository,
                $this->menuList,
                $this->menuTree,
                $this->menuTrail
            ),
            'page' => null // will be set by page render middleware
        ];
    }

    /**
     * @param Twig_Function $function
     */
    public function addFunction(Twig_Function $function)
    {
        $this->twig->addFunction($function);
    }

    /**
     * @param Twig_Filter $filter
     */
    public function addFilter(Twig_Filter $filter)
    {
        $this->twig->addFilter($filter);
    }

    /**
     * @param Twig_Test $test
     */
    public function addTest(Twig_Test $test)
    {
        $this->twig->addTest($test);
    }

    /**
     * @return void
     */
    private function addTwigPlugins()
    {
        // Functions
        $dir = $this->config->paths->twig->functions;
        foreach ($this->readPhpFiles($dir) as $file) {
            $included = $this->includePhpFile($file);
            $this->twig->addFunction($included);
        }
        // Filters
        $dir = $this->config->paths->twig->filters;
        foreach ($this->readPhpFiles($dir) as $file) {
            $included = $this->includePhpFile($file);
            $this->twig->addFilter($included);
        }
        // Tests
        $dir = $this->config->paths->twig->tests;
        foreach ($this->readPhpFiles($dir) as $file) {
            $included = $this->includePhpFile($file);
            $this->twig->addTest($included);
        }
    }

    /**
     * @return Twig_Loader_Filesystem
     * @throws \Twig_Error_Loader
     */
    private function getTwigFilesystemLoader()
    {
        $paths = [];
        if (empty($this->config->theme)) {
            $paths[] = $this->config->paths->layouts;
        } elseif ($this->config->theme === 'default') {
            $paths[] = $this->config->paths->layouts . '/default';
        } else {
            $paths[] = $this->config->paths->layouts . '/' . $this->config->theme;
            $paths[] = $this->config->paths->layouts . '/default';
        }

        $loader1 = new TwigStringLoader();
        $loader2 = new Twig_Loader_Filesystem($paths);

        // namespaces
        $namespaces = [
            'plugin' => $this->config->paths->plugins,
            'page' => $this->config->paths->pages,
            'site' => $this->config->paths->site,
            'widget' => $this->config->paths->app . '/../templates/widgets'
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
    private function includePhpFile($file)
    {
        return include($file);
    }

    /**
     * @param string $dir
     * @return array
     */
    private function readPhpFiles($dir)
    {
        $dir = rtrim($dir, '/');
        if (empty($dir) || !is_readable($dir)) {
            return [];
        }
        $pattern = $dir . '/*.php';
        return glob($pattern);
    }

    public function isInitialized()
    {
        return $this->initialized;
    }
}
