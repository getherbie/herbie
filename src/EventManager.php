<?php

declare(strict_types=1);

namespace Herbie;

class EventManager
{
    /**
     * @var array
     */
    private $events;

    /**
     * @var Event
     */
    private $eventPrototype;

    /**
     * EventManager constructor.
     * @param Event $eventPrototype
     */
    public function __construct(Event $eventPrototype)
    {
        $this->events = [];
        $this->eventPrototype = $eventPrototype;
    }

    /**
     * @inheritDoc
     */
    public function attach(string $eventName, callable $listener, int $priority = 1): callable
    {
        if (! is_string($eventName)) {
            throw new \InvalidArgumentException(sprintf(
                '%s expects a string for the event; received %s',
                __METHOD__,
                (is_object($eventName) ? get_class($eventName) : gettype($eventName))
            ));
        }

        $this->events[$eventName][(int) $priority][0][] = $listener;
        return $listener;
    }

    /**
     * @inheritDoc
     */
    public function trigger(string $eventName, $target = null, array $argv = []): void
    {
        $event = new $this->eventPrototype();
        $event->setName($eventName);

        if ($target !== null) {
            $event->setTarget($target);
        }

        if ($argv) {
            $event->setParams($argv);
        }

        $this->triggerListeners($event);
    }

    /**
     * Trigger listeners
     *
     * Actual functionality for triggering listeners, to which trigger() delegate.
     *
     * @param  EventInterface $event
     * @return void
     */
    private function triggerListeners(EventInterface $event): void
    {
        $name = $event->getName();

        if (empty($name)) {
            throw new \RuntimeException('Event is missing a name; cannot trigger!');
        }

        if (isset($this->events[$name])) {
            $listOfListenersByPriority = $this->events[$name];

            if (isset($this->events['*'])) {
                foreach ($this->events['*'] as $priority => $listOfListeners) {
                    $listOfListenersByPriority[$priority][] = $listOfListeners[0];
                }
            }
        } elseif (isset($this->events['*'])) {
            $listOfListenersByPriority = $this->events['*'];
        } else {
            $listOfListenersByPriority = [];
        }

        // Sort by priority in reverse order
        krsort($listOfListenersByPriority);

        // Initial value of stop propagation flag should be false
        $event->stopPropagation(false);

        // Execute listeners
        foreach ($listOfListenersByPriority as $listOfListeners) {
            foreach ($listOfListeners as $listeners) {
                foreach ($listeners as $listener) {
                    $listener($event);
                    // If the event was asked to stop propagating, do so
                    if ($event->propagationIsStopped()) {
                        return;
                    }
                }
            }
        }
    }
}
