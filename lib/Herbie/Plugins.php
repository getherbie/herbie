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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Plugins
{

    /**
     * @var Application
     */
    protected $app;

    /**
     * Constructor
     *
     * @param \Herbie\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Recurses through the plugins directory creating Plugin objects for each plugin it finds.
     *
     * @return array|Plugin[] array of Plugin objects
     * @throws \RuntimeException
     */
    public function init()
    {
        $path = $this->app['config']->get('plugins_path');
        if(empty($path) || !is_dir($path)) {
            return;
        }

        /** @var EventDispatcher $events */
        $events = $this->app['events'];
        
        $pluginKeys = array_keys($this->app['config']->get('plugins', []));
        foreach ($pluginKeys as $pluginKey) {

            $filePath = sprintf('%s/%s/%s.php', $path, $pluginKey, $pluginKey);
            if (!is_file($filePath)) {
                throw new \RuntimeException(sprintf("Plugin '%s' not found!", $filePath));
            }

            require_once $filePath;

            $pluginClass = '\\' . ucfirst($pluginKey) . 'Plugin';

            $instance = new $pluginClass($this->app);
            $events->addSubscriber($instance);
        }
    }
}
