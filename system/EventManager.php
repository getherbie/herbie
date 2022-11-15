<?php

declare(strict_types=1);

namespace herbie;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

final class EventManager implements EventDispatcherInterface, ListenerProviderInterface
{
    private array $listeners;

    public function __construct()
    {
        $this->listeners = [];
    }

    public function dispatch(object $event): object
    {
        // If the event is already stopped, nothing to do here.
        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return $event;
        }
        foreach ($this->getListenersForEvent($event) as $listener) {
            $listener($event);
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
        }
        return $event;
    }

    public function getListenersForEvent(object $event): iterable
    {
        $eventType = get_class($event);

        if (isset($this->listeners[$eventType])) {
            $listOfListenersByPriority = $this->listeners[$eventType];
        } else {
            $listOfListenersByPriority = [];
        }

        krsort($listOfListenersByPriority);

        foreach ($listOfListenersByPriority as $listOfListeners) {
            foreach ($listOfListeners as $listener) {
                yield $listener;
            }
        }
    }

    public function addListener(string $eventName, callable $listener, int $priority = 1): self
    {
        $this->listeners[$eventName][$priority][] = $listener;
        return $this;
    }

    public function getListeners(): array
    {
        return $this->listeners;
    }
}
