<?php

namespace herbie;

use Psr\Container\ContainerInterface;

final class InstallablePlugin
{
    private string $key;
    private string $path;
    private string $classPath;
    private string $type;

    public function __construct(string $key, string $path, string $classPath, string $type)
    {
        $this->key = $key;
        $this->path = $path;
        $this->classPath = $classPath;
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

    public function getClassPath(): string
    {
        return $this->classPath;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function classPathExists(): bool
    {
        return is_file($this->classPath) && is_readable($this->classPath);
    }

    public function requireClassPath(): string
    {
        $className = get_fully_qualified_class_name($this->classPath);
        if (!class_exists($className)) {
            require $this->classPath;
        }
        return $className;
    }

    /**
     * @throws SystemException
     */
    public function createPluginInstance(ContainerInterface $container): PluginInterface
    {
        $pluginClassName = $this->requireClassPath();

        $constructorParams = self::getConstructorParamsToInject(
            $pluginClassName,
            $container
        );

        return new $pluginClassName(...$constructorParams);
    }

    public static function getConstructorParamsToInject(string $pluginClassName, ContainerInterface $container): array
    {
        $reflectedClass = new \ReflectionClass($pluginClassName);
        $constructor = $reflectedClass->getConstructor();
        $constructorParams = [];
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->getType() === null) {
                    throw SystemException::serverError('Only objects can be injected in ' . $pluginClassName);
                }
                $classNameToInject = $param->getType()->getName();
                $constructorParams[] = $container->get($classNameToInject);
            }
        }
        return $constructorParams;
    }
}
