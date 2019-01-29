<?php

declare(strict_types=1);

namespace Herbie;

use Zend\EventManager\Event as ZendEvent;
use Zend\EventManager\EventInterface;

/**
 * Representation of an event
 *
 * Encapsulates the target context and parameters passed, and provides some
 * behavior for interacting with the event manager.
 */
class Event implements EventInterface
{
    /**
     * @var ZendEvent
     */
    protected $event;

    /**
     * Event constructor.
     * @param ZendEvent $event
     */
    public function __construct(ZendEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function getName()
    {
        return $this->event->getName();
    }

    /**
     * Get the event target
     *
     * This may be either an object, or the name of a static method.
     *
     * @return string|object
     */
    public function getTarget()
    {
        return $this->event->getTarget();
    }

    /**
     * Set parameters
     *
     * Overwrites parameters
     *
     * @param  array|\ArrayAccess|object $params
     * @throws \InvalidArgumentException
     */
    public function setParams($params)
    {
        $this->event->setParams($params);
    }

    /**
     * Get all parameters
     *
     * @return array|object|\ArrayAccess
     */
    public function getParams()
    {
        return $this->event->getParams();
    }

    /**
     * Get an individual parameter
     *
     * If the parameter does not exist, the $default value will be returned.
     *
     * @param  string|int $name
     * @param  mixed $default
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        return $this->event->getParam($name, $default);
    }

    /**
     * Set the event name
     *
     * @param  string $name
     */
    public function setName($name)
    {
        $this->event->setName($name);
    }

    /**
     * Set the event target/context
     *
     * @param  null|string|object $target
     */
    public function setTarget($target)
    {
        $this->event->setTarget($target);
    }

    /**
     * Set an individual parameter to a value
     *
     * @param  string|int $name
     * @param  mixed $value
     */
    public function setParam($name, $value)
    {
        $this->event->setParam($name, $value);
    }

    /**
     * Stop further event propagation
     *
     * @param  bool $flag
     */
    public function stopPropagation($flag = true)
    {
        $this->event->stopPropagation($flag);
    }

    /**
     * Is propagation stopped?
     *
     * @return bool
     */
    public function propagationIsStopped()
    {
        return $this->event->propagationIsStopped();
    }
}
