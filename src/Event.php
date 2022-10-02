<?php

declare(strict_types=1);

namespace herbie;

/**
 * Class Event
 */
final class Event implements EventInterface
{
    private string $name;

    /** @var mixed */
    private $target;

    private array $params;

    private bool $stopPropagation;

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

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param null $default
     * @return mixed|null
     */
    public function getParam(string $name, $default = null)
    {
        return $this->params[$name] ?? $default;
    }

    /**
     * Set the event name
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
     * @param mixed $value
     */
    public function setParam(string $name, $value): void
    {
        $this->params[$name] = $value;
    }

    public function stopPropagation(bool $flag = true): void
    {
        $this->stopPropagation = $flag;
    }

    public function propagationIsStopped(): bool
    {
        return $this->stopPropagation;
    }
}
