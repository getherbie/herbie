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
        /** @var EventDispatcher $events */
        $events = $this->app['events'];

        foreach (new \DirectoryIterator($this->app['sitePath'] . '/plugins/herbie') as $fileInfo) {
            if ($fileInfo->isDot())
                continue;

            $baseName = basename($fileInfo->getPathname());
            $filePath = sprintf('%s/%s.php', $fileInfo->getPathname(), $baseName);
            if (!is_file($filePath)) {
                throw new \RuntimeException(sprintf("Plugin '%s' not found!", $filePath));
            }

            require_once $filePath;

            $pluginClass = '\\' . ucfirst($baseName) . 'Plugin';

            $instance = new $pluginClass($this->app);
            if ($instance instanceof EventSubscriberInterface) {
                $events->addSubscriber($instance);
            }
        }
    }
}
