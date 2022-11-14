<?php

namespace herbie;

use Psr\Container\ContainerInterface;

final class InstallablePlugin
{
    private string $key;
    private string $path;
    /** @var class-string<PluginInterface> */
    private string $className; // as fully-qualified class name
    private string $type;

    /**
     * @param class-string<PluginInterface> $className
     */
    public function __construct(string $key, string $path, string $className, string $type)
    {
        $this->key = $key;
        $this->path = $path;
        $this->className = $className;
        $this->type = $type;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function createPluginInstance(ContainerInterface $container): PluginInterface
    {
        $constructorParams = get_constructor_params_to_inject(
            $this->className,
            $container
        );

        /** @var PluginInterface */
        return new $this->className(...$constructorParams);
    }
}
