<?php

declare(strict_types=1);

namespace herbie;

final class EventManager
{
    private array $events;

    private Event $eventPrototype;

    /**
     * EventManager constructor.
     */
    public function __construct(Event $eventPrototype)
    {
        $this->events = [];
        $this->eventPrototype = $eventPrototype;
    }

    public function attach(string $eventName, callable $listener, int $priority = 1): callable
    {
        $this->events[$eventName][$priority][0][] = $listener; // TODO reduce for one level
        return $listener;
    }

    /**
     * @param mixed $target
     */
    public function trigger(string $eventName, $target = null, array $argv = []): void
    {
        /** @var Event $event */
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

    public function getEvents(): array
    {
        $items = [];
        foreach ($this->events as $eventName => $eventsWithPriority) {
            foreach ($eventsWithPriority as $priority => $events) {
                foreach ($events as $event) {
                    foreach ($event as $e) {
                        $items[] = array_merge(
                            [$eventName, $priority],
                            get_callable_name($e)
                        );
                    }
                }
            }
        }
        return $items;
    }

    /**
     * Trigger listeners
     *
     * Actual functionality for triggering listeners, to which trigger() delegate.
     */
    private function triggerListeners(EventInterface $event): void
    {
        $name = $event->getName();

        if (empty($name)) {
            throw new \UnexpectedValueException('Event is missing a name; cannot trigger!');
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
