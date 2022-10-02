<?php

declare(strict_types=1);

namespace herbie;

/**
 * Interface EventInterface
 * @package Herbie
 */
interface EventInterface
{
    public function getName(): string;

    /**
     * @return mixed
     */
    public function getTarget();

    public function getParams(): array;

    /**
     * @param mixed|null $default
     * @return mixed
     */
    public function getParam(string $name, $default = null);

    public function setName(string $name): void;

    /**
     * @param mixed $target
     */
    public function setTarget($target): void;

    public function setParams(array $params): void;

    /**
     * @param mixed $value
     */
    public function setParam(string $name, $value): void;

    /**
     * @param bool $flag
     * @return void
     */
    public function stopPropagation(bool $flag = true): void;

    public function propagationIsStopped(): bool;
}
