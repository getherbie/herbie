<?php

declare(strict_types=1);

namespace Herbie;

/**
 * Class Event
 * @package Herbie
 */
class Event implements EventInterface
{
    /** @var string */
    private $name;
    /** @var mixed */
    private $target;
    /** @var array */
    private $params;
    /** @var bool */
    private $stopPropagation;

    /**
     * Event constructor.
     */
    public function __construct()
    {
        $this->name = '';
        $this->target = null;
        $this->params = [];
        $this->stopPropagation = false;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param string $name
     * @param null $default
     * @return mixed|null
     */
    public function getParam(string $name, $default = null)
    {
        return $this->params[$name] ?? $default;
    }

    /**
     * Set the event name
     *
     * @param  string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param mixed $target
     */
    public function setTarget($target): void
    {
        $this->target = $target;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setParam(string $name, $value): void
    {
        $this->params[$name] = $value;
    }

    /**
     * @param bool $flag
     */
    public function stopPropagation(bool $flag = true): void
    {
        $this->stopPropagation = $flag;
    }

    /**
     * @return bool
     */
    public function propagationIsStopped(): bool
    {
        return $this->stopPropagation;
    }
}
