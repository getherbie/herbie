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

use Twig_Environment;
use Twig_Extension_Debug;
use Twig_Loader_Chain;
use Twig_Loader_Filesystem;
use Twig_Loader_String;

class Twig
{

    /**
     * @var \Herbie\Application
     */
    public $app;

    /**
     * @var type \Herbie\Config
     */
    public $config;

    /**
     * @var \Twig_Environment
     */
    public $environment;

    /**
     * Constructor
     *
     * @param \Herbie\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $app['config'];
    }

    public function init()
    {
        $loader1 = $this->getTwigFilesystemLoader();
        $loader2 = new Twig_Loader_String();
        $loaderChain = new Twig_Loader_Chain([$loader1, $loader2]);

        $this->environment = new Twig_Environment($loaderChain, [
            'debug' => $this->config->get('twig.debug'),
            'cache' => $this->config->get('twig.cache')
        ]);

        if (!$this->config->isEmpty('twig.debug')) {
            $this->environment->addExtension(new Twig_Extension_Debug());
        }
        $this->environment->addExtension(new Twig\HerbieExtension($this->app));
        if (!$this->config->isEmpty('imagine')) {
            #$this->environment->addExtension(new Twig\ImagineExtension($this->app));
        }
        $this->addTwigPlugins();
    }

    /**
     * @param string $name
     * @param array $context
     * @return string
     */
    public function render($name, array $context = array())
    {
        $context = array_merge($context, [
            'route' => $this->app->getRoute(),
            'baseUrl' => $this->app['request']->getBasePath(),
            'theme' => $this->app['config']->get('theme')
        ]);
        return $this->environment->render($name, $context);
    }

    /**
     * @return void
     */
    public function addTwigPlugins()
    {
        if ($this->config->isEmpty('twig.extend')) {
            return;
        }

        $app = $this->app; // Global $app var used by plugins

        extract($this->config->get('twig.extend')); // functions, filters, tests
        // Functions
        if (isset($functions)) {
            foreach ($this->readPhpFiles($functions) as $file) {
                $included = $this->includePhpFile($file);
                $this->environment->addFunction($included);
            }
        }

        // Filters
        if (isset($filters)) {
            foreach ($this->readPhpFiles($filters) as $file) {
                $included = $this->includePhpFile($file);
                $this->environment->addFilter($included);
            }
        }

        // Tests
        if (isset($tests)) {
            foreach ($this->readPhpFiles($tests) as $file) {
                $included = $this->includePhpFile($file);
                $this->environment->addTest($included);
            }
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
        $loader->addPath(__DIR__ . '/Twig/widgets', 'widget');
        if(!$this->config->isEmpty('plugins_path')) {
            $loader->addPath($this->config->get('plugins_path'), 'plugins');
        }
        return $loader;
    }

    /**
     * @param string $file
     * @return string
     */
    private function includePhpFile($file)
    {
        $app = $this->app; // Global $app var used by plugins
        return include($file);
    }

    /**
     * @param string $dir
     * @return array
     */
    private function readPhpFiles($dir)
    {
        $dir = rtrim($dir, '/');
        if (empty($dir) || !is_dir($dir)) {
            return [];
        }
        $pattern = $dir . '/*.php';
        return glob($pattern);
    }
}
