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
     * @var Application
     */
    public $app;

    /**
     * @var \Twig_Environment
     */
    public $environment;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function init()
    {
        $config = $app['config'];

        $loader = $this->getTwigFilesystemLoader($config);

        $this->environment = new Twig_Environment($loader, [
            'debug' => $config['twig']['debug'],
            'cache' => $config['twig']['cache']
        ]);

        if (!empty($config['twig']['debug'])) {
            $this->environment->addExtension(new Twig_Extension_Debug());
        }
        $this->environment->addExtension(new Twig\HerbieExtension($app));
        if (!empty($config['imagine'])) {
            $this->environment->addExtension(new Twig\ImagineExtension($app));
        }
        $this->addTwigPlugins($config);
    }

    /**
     * @param string $name
     * @param array $context
     * @return string
     */
    public function render($name, array $context = array())
    {
        return $this->environment->render($name, $context);
    }

    /**
     * @param array $config
     */
    public function addTwigPlugins(array $config)
    {
        if (empty($config['twig']['extend'])) {
            return;
        }

        extract($config['twig']['extend']); // functions, filters, tests
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
     * @param array $config
     * @return Twig_Loader_Filesystem
     */
    private function getTwigFilesystemLoader($config)
    {
        $paths = [];
        if (empty($config['theme'])) {
            $paths[] = $config['layouts']['path'];
        } elseif ($config['theme'] == 'default') {
            $paths[] = $config['layouts']['path'] . '/default';
        } else {
            $paths[] = $config['layouts']['path'] . '/' . $config['theme'];
            $paths[] = $config['layouts']['path'] . '/default';
        }
        $paths[] = __DIR__ . '/layouts'; // Fallback

        $loader = new Twig_Loader_Filesystem($paths);
        $loader->addPath(__DIR__ . '/Twig/widgets', 'widget');
        return $loader;
    }

    /**
     * @param string $file
     * @return string
     */
    private function includePhpFile($file)
    {
        $app = $this; // Global $app var used by plugins
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
