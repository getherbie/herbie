<?php

namespace herbie;

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
    
    public function classPathExists(): bool
    {
        return is_file($this->classPath) && is_readable($this->classPath);
    }
    
    public function requireClassPath(): string
    {
        $className = $this->getClassName();
        if (!class_exists($className)) {
            require $this->classPath;
        }
        return $className;
    }

    /**
     * @throws SystemException
     */
    public function createPluginInstance(Container $container): PluginInterface
    {
        $pluginClassName = $this->requireClassPath();

        $constructorParams = self::getConstructorParamsToInject(
            $pluginClassName,
            $container
        );

        return new $pluginClassName(...$constructorParams);
    }

    /**
     * @see https://stackoverflow.com/questions/7153000/get-class-name-from-file
     */
    private function getClassName(): string
    {
        $fp = fopen($this->classPath, 'r');
        $class = $namespace = $buffer = '';
        $i = 0;
        while (!$class) {
            if (feof($fp)) {
                break;
            }
            $buffer .= fread($fp, 256);
            $tokens = @token_get_all($buffer);
            
            if (strpos($buffer, '{') === false) {
                continue;
            }
            
            for (; $i<count($tokens); $i++) {
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j=$i+1; $j<count($tokens); $j++) {
                        if ($tokens[$j][0] === T_STRING) {
                            $namespace .= '\\'.$tokens[$j][1];
                        } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                            break;
                        }
                    }
                }

                if ($tokens[$i][0] === T_CLASS) {
                    for ($j=$i+1; $j<count($tokens); $j++) {
                        if ($tokens[$j] === '{') {
                            $class = $tokens[$i+2][1];
                            break;
                        }
                    }
                }
            }
        }
        
        if (strlen($namespace) === 0) {
            return $class;
        }
        
        return $namespace . '\\' . $class;
    }

    public static function getConstructorParamsToInject(string $pluginClassName, Container $container): array
    {
        $reflectedClass = new \ReflectionClass($pluginClassName);
        $constructor = $reflectedClass->getConstructor();
        $constructorParams = [];
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->getType() === null) {
                    throw SystemException::serverError('Only objects can be injected in ' . $pluginClassName);
                }
                $classNameToInject = $param->getClass()->getName();
                $constructorParams[] = $container->get($classNameToInject);
            };
        }
        return $constructorParams;
    }
}
