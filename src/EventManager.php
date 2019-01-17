<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-01-09
 * Time: 05:15
 */

declare(strict_types=1);

namespace Herbie;

use Zend\EventManager\EventManager as EventManagerAlias;

class EventManager
{
    /**
     * @var EventManagerAlias
     */
    private $eventManager;

    /**
     * EventManager constructor.
     * @param EventManagerAlias $eventManager
     */
    public function __construct(EventManagerAlias $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @param string $eventName
     * @param callable $listener
     * @param int $priority
     * @return callable
     */
    public function attach(string $eventName, callable $listener, int $priority = 1): callable
    {
        return $this->eventManager->attach($eventName, $listener, $priority);
    }

    /**
     * @param string $eventName
     * @param null $target
     * @param array $argv
     * @return void
     */
    public function trigger(string $eventName, $target = null, array $argv = []): void
    {
        $this->eventManager->trigger($eventName, $target, $argv);
    }
}
