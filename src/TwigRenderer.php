<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-01-06
 * Time: 08:52
 */

namespace Herbie;

use Ausi\SlugGenerator\SlugGeneratorInterface;
use Herbie\Menu\MenuList;
use Herbie\Menu\MenuTree;
use Herbie\Menu\RootPath;
use Herbie\Repository\DataRepositoryInterface;
use Herbie\Url\UrlGenerator;
use Psr\Http\Message\ServerRequestInterface;
use Twig_Environment;
use Twig_Extension_Debug;
use Twig_Filter;
use Twig_Function;
use Twig_Loader_Array;
use Twig_Loader_Filesystem;
use Zend\EventManager\EventManagerInterface;

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
    private $request;
    private $urlGenerator;
    private $translator;
    private $slugGenerator;
    private $assets;
    private $menuList;
    private $menuTree;
    private $menuRootPath;
    private $dataRepository;

    /**
     * TwigRenderer constructor.
     * @param Alias $alias
     * @param Config $config
     * @param ServerRequestInterface $request
     * @param UrlGenerator $urlGenerator
     * @param SlugGeneratorInterface $slugGenerator
     * @param Assets $assets
     * @param MenuList $menuList
     * @param MenuTree $menuTree
     * @param RootPath $menuRootPath
     * @param Environment $environment
     * @param DataRepositoryInterface $dataRepository
     * @param Translator $translator
     * @param EventManagerInterface $eventManager
     * @throws \Twig_Error_Loader
     */
    public function __construct(
        Alias $alias,
        Config $config,
        ServerRequestInterface $request,
        UrlGenerator $urlGenerator,
        SlugGeneratorInterface $slugGenerator,
        Assets $assets,
        MenuList $menuList,
        MenuTree $menuTree,
        RootPath $menuRootPath,
        Environment $environment,
        DataRepositoryInterface $dataRepository,
        Translator $translator,
        EventManagerInterface $eventManager
    ) {
        $this->initialized = false;
        $this->environment = $environment;
        $this->alias = $alias;
        $this->config = $config;
        $this->request = $request;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->slugGenerator = $slugGenerator;
        $this->assets = $assets;
        $this->menuList = $menuList;
        $this->menuTree = $menuTree;
        $this->menuRootPath = $menuRootPath;
        $this->environment = $environment;
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
            'debug' => $this->config->get('twig.debug'),
            'cache' => $this->config->get('twig.cache')
        ]);

        if (!$this->config->isEmpty('twig.debug')) {
            $this->twig->addExtension(new Twig_Extension_Debug());
        }

        $herbieExtension = new TwigExtension(
            $this->alias,
            $this->config,
            $this->request,
            $this->urlGenerator,
            $this->slugGenerator,
            $this->assets,
            $this->menuList,
            $this->menuTree,
            $this->menuRootPath,
            $this->environment,
            $this->dataRepository,
            $this->translator,
            $this
        );

        $this->twig->addExtension($herbieExtension);

        $this->twig->addGlobal('site', new Site(
            $this->config,
            $this->dataRepository,
            $this->menuList,
            $this->menuTree,
            $this->menuRootPath
        ));

        $this->twig->addGlobal('page', $this->request->getAttribute(Page::class));

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
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderString(string $string, array $context = []): string
    {
        $name = '__render_string__';

        // get current loader
        $currentLoader = $this->twig->getLoader();

        // set loader chain with new array loader
        $this->twig->setLoader(new Twig_Loader_Array([$name => $string]));

        // render string
        $context = array_merge($context, $this->getContext());
        $rendered = $this->twig->render($name, $context);

        // set current loader
        $this->twig->setLoader($currentLoader);
        return $rendered;
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
        $context = array_merge($context, $this->getContext());
        return $this->twig->render($name, $context);
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
     * @return array
     */
    private function getContext()
    {
        return [
            'route' => $this->environment->getRoute(),
            'baseUrl' => $this->environment->getBaseUrl(),
            'theme' => $this->config->get('theme')
        ];
    }

    /**
     * @return void
     */
    private function addTwigPlugins()
    {
        if ($this->config->isEmpty('twig.extend')) {
            return;
        }
        // Functions
        $dir = $this->config->get('twig.extend.functions');
        foreach ($this->readPhpFiles($dir) as $file) {
            $included = $this->includePhpFile($file);
            $this->twig->addFunction($included);
        }
        // Filters
        $dir = $this->config->get('twig.extend.filters');
        foreach ($this->readPhpFiles($dir) as $file) {
            $included = $this->includePhpFile($file);
            $this->twig->addFilter($included);
        }
        // Tests
        $dir = $this->config->get('twig.extend.tests');
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
        if ($this->config->isEmpty('theme')) {
            $paths[] = $this->config->get('layouts.path');
        } elseif ($this->config->get('theme') == 'default') {
            $paths[] = $this->config->get('layouts.path') . '/default';
        } else {
            $paths[] = $this->config->get('layouts.path') . '/' . $this->config->get('theme');
            $paths[] = $this->config->get('layouts.path') . '/default';
        }

        $loader = new Twig_Loader_Filesystem($paths);

        // namespaces
        $namespaces = [
            'plugin' => $this->config->get('plugins.path'),
            'page' => $this->config->get('pages.path'),
            'site' => $this->config->get('site.path'),
            'widget' => $this->config->get('app.path') . '/../templates/widgets'
        ];
        foreach ($namespaces as $namespace => $path) {
            if (is_readable($path)) {
                $loader->addPath($path, $namespace);
            }
        }

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
