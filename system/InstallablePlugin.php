<?php

namespace herbie;

use Psr\Container\ContainerInterface;

final readonly class InstallablePlugin
{
    /**
     * @param class-string<PluginInterface> $className
     */
    public function __construct(
        private string $key,
        private string $path,
        /** @var class-string<PluginInterface> */
        private string $className,
        private string $type
    ) {
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
        $constructorParams = di_constructor_params_from_container(
            $this->className,
            $container,
            di_class_whitelist()
        );

        /** @var PluginInterface */
        return new $this->className(...$constructorParams);
    }
}
