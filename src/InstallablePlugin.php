<?php

namespace herbie;

class InstallablePlugin
{
    private string $key;
    private string $path;
    private string $class;
    private string $classPath;
    
    public function __construct(string $key, string $path, string $class)
    {
        $this->key = $key;
        $this->path = $path;
        $this->class = $class;
        $this->classPath = sprintf('%s/%s', $path, $class);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getClass(): string
    {
        return $this->class;
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
        require $this->classPath;
        return $this->getClassName();
    }

    /**
     * @throws SystemException
     */
    public function createPluginInstance(Container $container): object
    {
        $pluginClassName = $this->requireClassPath();

        $constructorParams = self::getConstructorParamsToInject(
            $pluginClassName,
            $container
        );

        /** @var PluginInterface $plugin */
        return new $pluginClassName(...$constructorParams);
    }

    /**
     * @return string
     * @see https://stackoverflow.com/questions/7153000/get-class-name-from-file
     */
    private function getClassName(): string
    {
        $fp = fopen($this->classPath, 'r');
        $class = $buffer = '';
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
        
        return $class;
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
