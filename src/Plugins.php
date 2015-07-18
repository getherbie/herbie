<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <http://www.tebe.ch>
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
     * @var array
     */
    private $items;

    /**
     * @var array
     */
    private $dirs;

    /**
     * @param \Herbie\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->items = [];
        $this->dirs = null;
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
                $message = $this->app['translator']->t('app', 'Plugin "{plugin}" enabled but not found!', ['{plugin}' => $pluginKey]);
                throw new \RuntimeException($message);
            }

            $pluginClass = '\\herbie\\plugin\\' . $pluginKey . '\\' . ucfirst($pluginKey) . 'Plugin';
            $instance = new $pluginClass($this->app);
            $events->addSubscriber($instance);
            $this->addItem($pluginKey, $filePath, $pluginClass);
        }
    }

    /**
     * @param string $key
     * @param string $path
     * @param string $class
     */
    private function addItem($key, $path, $class)
    {
        $this->items[] = [
            'key' => $key,
            'dir' => dirname($path),
            'path' => $path,
            'class' => $class
        ];
    }

    /**
     * @return array
     */
    public function getDirectories()
    {
        if (is_null($this->dirs)) {
            $this->dirs = [];
            foreach($this->items as $item) {
                $key = $item['key'];
                $this->dirs[$key] = $item['dir'];
            }
        }
        return $this->dirs;
    }

}
