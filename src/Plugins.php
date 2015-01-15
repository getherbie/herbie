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
     * @param \Herbie\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @throws \RuntimeException
     */
    public function init()
    {
        /** @var EventDispatcher $events */
        $events = $this->app['events'];

        $pluginPath = rtrim($this->app['config']->get('plugins.path'), '/');
        $pluginList = $this->app['config']->get('plugins.enable', []);
        foreach ($pluginList as $pluginKey) {

            $filePath = sprintf(
                '%s/%s/%sPlugin.php', $pluginPath, $pluginKey, ucfirst($pluginKey)
            );
            
            if (!is_file($filePath)) {
                throw new \RuntimeException(sprintf("Plugin '%s' enabled but not found!", $pluginKey));
            }

            $pluginClass = '\\herbie\\plugin\\' . $pluginKey . '\\' . ucfirst($pluginKey) . 'Plugin';
            $instance = new $pluginClass($this->app);
            $events->addSubscriber($instance);
        }
    }
}
