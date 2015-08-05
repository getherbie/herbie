<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

use Symfony\Component\HttpFoundation\Response;
use Twig_Environment;
use Twig_Extension_Debug;
use Twig_Loader_Chain;
use Twig_Loader_Filesystem;
use Twig_Loader_Array;

class Twig
{

    /**
     * @var Config
     */
    public $config;

    /**
     * @var \Twig_Environment
     */
    public $environment;

    /**
     * @var boolean
     */
    private $initialized;

    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->initialized = false;
    }

    public function init()
    {
        $loader = $this->getTwigFilesystemLoader();
        $this->environment = new Twig_Environment($loader, [
            'debug' => $this->config->get('twig.debug'),
            'cache' => $this->config->get('twig.cache')
        ]);
        if (!$this->config->isEmpty('twig.debug')) {
            $this->environment->addExtension(new Twig_Extension_Debug());
        }
        $this->environment->addExtension(new Twig\HerbieExtension());
        $this->addTwigPlugins();
        $this->initialized = true;
    }

    /**
     * @param string $name
     * @param array $context
     * @return string
     */
    public function render($name, array $context = [])
    {
        $context = array_merge($context, $this->getContext());
        return $this->environment->render($name, $context);
    }

    /**
     * Renders a page and returns a response object.
     * Catches every exception that occured during the rendering process.
     * @param Page $page
     * @return Response
     */
    public function renderPage(Page $page)
    {

        try {

            if (empty($page->layout)) {
                $content = $this->renderPageSegment(0, $page);
            } else {
                $content = $this->render($page->layout);
            }

        } catch (\Exception $e) {

            $page->setError($e);
            $content = $this->render('error.html');

        }

        $response = new Response($content);
        $response->setStatusCode($page->getStatusCode());
        $response->headers->set('Content-Type', $page->content_type);

        return $response;
    }

    /**
     * Renders a page content segment.
     * @param string|int $segmentId
     * @param Page $page
     * @return string
     */
    public function renderPageSegment($segmentId, Page $page = null)
    {
        if (is_null($page)) {
            $page = Application::getPage();
        }

        $segment = $page->getSegment($segmentId);
        Application::fireEvent('onContentSegmentLoaded', ['segment' => &$segment]);

        $twigged = Application::getService('Twig')->renderString($segment);
        Application::fireEvent('onContentSegmentTwigged', ['twigged' => &$twigged]);

        $formatter = Formatter\FormatterFactory::create($page->format);
        $rendered = $formatter->transform($twigged);
        Application::fireEvent('onContentSegmentRendered', ['segment' => &$rendered]);

        return $rendered;
    }

    /**
     * @param string $string
     * @return string
     */
    public function renderString($string)
    {
        // no rendering if empty
        if (empty($string)) {
            return $string;
        }
        // see Twig\Extensions\Twig_Extension_StringLoader
        $name = '__twig_string__';
        // get current loader
        $loader = $this->environment->getLoader();
        // set loader chain with new array loader
        $this->environment->setLoader(new Twig_Loader_Chain(array(
            new Twig_Loader_Array(array($name => $string)),
            $loader
        )));
        // render string
        $context = $this->getContext();
        $rendered = $this->environment->render($name, $context);
        // reset current loader
        $this->environment->setLoader($loader);
        return $rendered;
    }

    /**
     * @return array
     */
    private function getContext()
    {
        // @todo Inject request object or refactor code
        return [
            'route' => Application::getService('Request')->getRoute(),
            'baseUrl' => Application::getService('Request')->getBasePath(),
            'theme' => $this->config->get('theme')
        ];
    }

    /**
     * @return void
     */
    public function addTwigPlugins()
    {
        if ($this->config->isEmpty('twig.extend')) {
            return;
        }
        // Functions
        $dir = $this->config->get('twig.extend.functions');
        foreach ($this->readPhpFiles($dir) as $file) {
            $included = $this->includePhpFile($file);
            $this->environment->addFunction($included);
        }
        // Filters
        $dir = $this->config->get('twig.extend.filters');
        foreach ($this->readPhpFiles($dir) as $file) {
            $included = $this->includePhpFile($file);
            $this->environment->addFilter($included);
        }
        // Tests
        $dir = $this->config->get('twig.extend.tests');
        foreach ($this->readPhpFiles($dir) as $file) {
            $included = $this->includePhpFile($file);
            $this->environment->addTest($included);
        }
    }

    /**
     * @return Twig_Loader_Filesystem
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
        $paths[] = __DIR__ . '/layouts'; // Fallback

        $loader = new Twig_Loader_Filesystem($paths);

        // namespaces
        $namespaces = [
            'plugin' => $this->config->get('plugins.path'),
            'page' => $this->config->get('pages.path'),
            'post' => $this->config->get('posts.path'),
            'site' => $this->config->get('site.path'),
            'widget' => __DIR__ . '/Twig/widgets'
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
        // @todo Don't use $app in twig plugins
        #$app = $this->app; // Global $app var used by plugins
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

    /**
     * @return bool
     */
    public function isInitialized()
    {
        return true === $this->initialized;
    }

}
