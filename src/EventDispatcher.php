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

class EventDispatcher
{
    private $listeners = [];

    /**
     * @param string $eventName
     * @param Event $event
     * @return Event
     */
    public function dispatch($eventName, Event $event)
    {
        if (!isset($this->listeners[$eventName])) {
            return $event;
        }

        foreach ($this->listeners[$eventName] as $listener) {
            call_user_func([$listener, $eventName], $event);
        }

        return $event;
    }

    /**
     * @param string $eventName
     * @return array
     */
    public function getListeners($eventName)
    {
        return isset($this->listeners[$eventName]) ? $this->listeners[$eventName] : [];
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
        return (bool)count($this->getListeners($eventName));
    }

    /**
     * @param string $eventName
     * @param Plugin $plugin
     */
    public function addListener($eventName, Plugin $plugin)
    {
        $this->listeners[$eventName][] = $plugin;
    }

    /**
     * @param Plugin $plugin
     */
    public function addPlugin(Plugin $plugin)
    {
        foreach ($plugin->getSubscribedEvents() as $eventName) {
            $this->addListener($eventName, $plugin);
        }
    }

}
