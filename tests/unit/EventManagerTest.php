<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\EventManager;
use Psr\EventDispatcher\StoppableEventInterface;

final class EventManagerTest extends Unit
{
    public function testDispatchWithoutListenersReturnsEvent(): void
    {
        $manager = new EventManager();
        $event = new \stdClass();

        $this->assertSame($event, $manager->dispatch($event));
    }

    public function testListenerIsCalled(): void
    {
        $manager = new EventManager();
        $calls = [];

        $manager->addListener(\stdClass::class, static function () use (&$calls): void {
            $calls[] = 'called';
        });

        $manager->dispatch(new \stdClass());

        $this->assertSame(['called'], $calls);
    }

    public function testListenerReceivesTheEvent(): void
    {
        $manager = new EventManager();
        $received = null;
        $event = new StoppableTestEvent();

        $manager->addListener(StoppableTestEvent::class, static function ($e) use (&$received): void {
            $received = $e;
        });

        $manager->dispatch($event);

        $this->assertSame($event, $received);
    }

    public function testListenersAreCalledForTheExactEventClassOnly(): void
    {
        $manager = new EventManager();
        $calls = [];

        $manager->addListener(\stdClass::class, static function () use (&$calls): void {
            $calls[] = 'std';
        });

        $manager->dispatch(new StoppableTestEvent());

        $this->assertSame([], $calls);
    }

    public function testListenersAreCalledInRegistrationOrder(): void
    {
        $manager = new EventManager();
        $calls = [];

        $manager->addListener(\stdClass::class, static function () use (&$calls): void {
            $calls[] = 'first';
        });
        $manager->addListener(\stdClass::class, static function () use (&$calls): void {
            $calls[] = 'second';
        });

        $manager->dispatch(new \stdClass());

        $this->assertSame(['first', 'second'], $calls);
    }

    public function testHigherPriorityIsCalledFirst(): void
    {
        $manager = new EventManager();
        $calls = [];

        $manager->addListener(\stdClass::class, static function () use (&$calls): void {
            $calls[] = 'low';
        }, 1);
        $manager->addListener(\stdClass::class, static function () use (&$calls): void {
            $calls[] = 'high';
        }, 10);

        $manager->dispatch(new \stdClass());

        $this->assertSame(['high', 'low'], $calls);
    }

    public function testNegativePriorityIsCalledLast(): void
    {
        $manager = new EventManager();
        $calls = [];

        $manager->addListener(\stdClass::class, static function () use (&$calls): void {
            $calls[] = 'negative';
        }, -5);
        $manager->addListener(\stdClass::class, static function () use (&$calls): void {
            $calls[] = 'default';
        });

        $manager->dispatch(new \stdClass());

        $this->assertSame(['default', 'negative'], $calls);
    }

    public function testAddListenerIsFluent(): void
    {
        $manager = new EventManager();

        $this->assertSame($manager, $manager->addListener(\stdClass::class, static fn(): null => null));
    }

    public function testGetListeners(): void
    {
        $manager = new EventManager();
        $manager->addListener(\stdClass::class, static fn(): null => null);

        $listeners = $manager->getListeners();

        $this->assertArrayHasKey(\stdClass::class, $listeners);
    }

    public function testStoppedEventIsNotDispatched(): void
    {
        $manager = new EventManager();
        $calls = [];
        $event = new StoppableTestEvent();
        $event->stopPropagation();

        $manager->addListener(StoppableTestEvent::class, static function () use (&$calls): void {
            $calls[] = 'called';
        });

        $manager->dispatch($event);

        $this->assertSame([], $calls);
    }

    public function testPropagationStopsAfterListenerStopsIt(): void
    {
        $manager = new EventManager();
        $calls = [];
        $event = new StoppableTestEvent();

        $manager->addListener(StoppableTestEvent::class, static function (StoppableTestEvent $e) use (&$calls): void {
            $calls[] = 'first';
            $e->stopPropagation();
        });
        $manager->addListener(StoppableTestEvent::class, static function () use (&$calls): void {
            $calls[] = 'second';
        });

        $manager->dispatch($event);

        $this->assertSame(['first'], $calls);
    }
}

final class StoppableTestEvent implements StoppableEventInterface
{
    private bool $stopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }

    public function stopPropagation(): void
    {
        $this->stopped = true;
    }
}
