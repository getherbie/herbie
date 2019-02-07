<?php

declare(strict_types=1);

namespace Herbie;

/**
 * Interface EventInterface
 * @package Herbie
 */
interface EventInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return mixed
     */
    public function getTarget();

    /**
     * @return array
     */
    public function getParams(): array;

    /**
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public function getParam(string $name, $default = null);

    /**
     * @param string $name
     */
    public function setName(string $name): void;

    /**
     * @param mixed $target
     */
    public function setTarget($target): void;

    /**
     * @param array $params
     */
    public function setParams(array $params): void;

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setParam(string $name, $value): void;

    /**
     * @param bool $flag
     * @return mixed
     */
    public function stopPropagation(bool $flag = true): void;

    /**
     * @return bool
     */
    public function propagationIsStopped(): bool;
}
