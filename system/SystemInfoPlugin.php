<?php

declare(strict_types=1);

namespace herbie;

use Closure;
use ReflectionException;
use ReflectionFunction;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class SystemInfoPlugin extends Plugin
{
    private string $appPath;

    public function __construct(
        private Alias $alias,
        private Config $config,
        private EventManager $eventManager,
        private MiddlewareDispatcher $middlewareDispatcher,
        private PluginManager $pluginManager,
        private Translator $translator,
        private TwigRenderer $twigRenderer,
    ) {
        $this->appPath = str_untrailing_slash($config->getAsString('paths.app'));
    }

    public function twigFunctions(): array
    {
        return [
            ['herbie_info', [$this, 'herbieInfo'], ['is_safe' => ['html'], 'needs_context' => true]],
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function herbieInfo(array $context, string $template = '@snippet/herbie_info.twig'): string
    {
        $info = [
            'aliases' => $this->getAlias(),
            'commands' => $this->getConsoleCommands(),
            'configs' => $this->getConfig(),
            'events' => $this->getEventListeners(),
            'middlewares' => $this->getMiddlewares(),
            'php_classes' => $this->definedClasses('herbie'),
            'php_functions' => $this->definedFunctions('herbie'),
            'plugins' => $this->getPlugins(),
            'translations' => $this->getTranslations(),
            'twig_globals' => $this->getTwigGlobalsFromContext($context),
            'twig_filters' => $this->getTwigFilters(),
            'twig_functions' => $this->getTwigFunctions(),
            'twig_tests' => $this->getTwigTests(),
        ];
        return $this->twigRenderer->renderTemplate($template, $info);
    }

    private function getAlias(): array
    {
        $items = [];
        foreach ($this->alias->getAll() as $key => $value) {
            $items[] = [$key, $this->filterValue($value)];
        }
        return $items;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function filterValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $replaceIfEquals = [$this->appPath => '/'];
        foreach ($replaceIfEquals as $k => $v) {
            if ($k === $value) {
                $value = $v;
            }
        }

        $stripFromBeginning = [$this->appPath];
        foreach ($stripFromBeginning as $v) {
            if (strpos($value, $v) === 0) {
                $value = substr($value, strlen($v));
            }
        }

        // filter emails
        if (strpos($value, '@') > 0) {
            $value = '~filtered~';
        }

        return $value;
    }

    /**
     * @return string[]
     */
    private function getConsoleCommands(): array
    {
        $items = [];
        foreach ($this->pluginManager->getConsoleCommands() as $command) {
            $items[] = $command;
        }
        return $items;
    }

    /**
     * @return array<int, array{string, string, mixed}>
     */
    private function getConfig(): array
    {
        $configs = [];
        foreach ($this->config->flatten() as $key => $value) {
            $configs[] = [
                $key,
                get_type($value),
                $this->filterValue($value),
            ];
        }
        return $configs;
    }

    /**
     * @return array<int, string[]>
     */
    private function getEventListeners(): array
    {
        $items = [];
        foreach ($this->eventManager->getListeners() as $eventName => $eventsWithPriority) {
            foreach ($eventsWithPriority as $priority => $events) {
                foreach ($events as $event) {
                    $items[] = array_merge(
                        [$eventName, (string)$priority],
                        $this->getCallableName($event),
                    );
                }
            }
        }
        return $items;
    }

    /**
     * @return array<int, string[]>
     */
    private function getMiddlewares(): array
    {
        $info = [];
        foreach ($this->middlewareDispatcher->getMiddlewares() as $middleware) {
            if (is_array($middleware) && (is_string($middleware[0]))) {
                $type = 'ROUTE';
                $callable = $this->getCallableName($middleware[1]);
            } else {
                $type = 'APP';
                $callable = $this->getCallableName($middleware);
            }
            $info[] = [
                $type,
                $callable[0],
                $callable[1],
            ];
        }
        return $info;
    }

    /**
     * @return array<int, string[]>
     */
    private function getPlugins(): array
    {
        $plugins = [];
        foreach ($this->pluginManager->getLoadedPlugins() as $plugin) {
            $plugins[] = [
                $plugin->getKey(),
                $plugin->getType(),
                $plugin->getClassName(),
            ];
        }
        return $plugins;
    }

    private function getTranslations(): array
    {
        $items = [];
        foreach ($this->translator->getMessages() as $language => $messagesPerLanguage) {
            ksort($messagesPerLanguage);
            foreach ($messagesPerLanguage as $category => $messagesPerCategory) {
                ksort($messagesPerCategory);
                foreach ($messagesPerCategory as $english => $translation) {
                    $items[] = [$category, $english, $translation, $language];
                }
            }
        }
        return $items;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, mixed>
     */
    private function getTwigGlobalsFromContext(array $context): array
    {
        $globals = [];
        foreach ($context as $string => $mixed) {
            if (is_scalar($mixed)) {
                $value = $mixed;
                $type = get_type($mixed);
            } elseif (is_object($mixed)) {
                $value = get_class($mixed);
                $type = 'class';
            } else {
                $value = json_encode($mixed);
                $type = get_type($mixed);
            }
            $globals[] = [$string, $value, $type];
        }
        return $globals;
    }

    /**
     * @return array<int, string[]>
     */
    private function getTwigFilters(): array
    {
        $items = [];
        foreach ($this->twigRenderer->getTwigEnvironment()->getFilters() as $f) {
            $callable = $f->getCallable() ?? $f->getName();
            $items[] = [
                $f->getName(),
                ...$this->getCallableName($callable),
            ];
        }
        return $items;
    }

    /**
     * @return array<int, string[]>
     */
    private function getTwigFunctions(): array
    {
        $items = [];
        foreach ($this->twigRenderer->getTwigEnvironment()->getFunctions() as $f) {
            $callable = $f->getCallable() ?? $f->getName();
            $items[] = [
                $f->getName(),
                ...$this->getCallableName($callable),
            ];
        }
        return $items;
    }

    /**
     * @return array<int, string[]>
     */
    private function getTwigTests(): array
    {
        $items = [];
        foreach ($this->twigRenderer->getTwigEnvironment()->getTests() as $f) {
            $callable = $f->getCallable() ?? $f->getName();
            $items[] = [
                $f->getName(),
                ...$this->getCallableName($callable),
            ];
        }
        return $items;
    }

    /**
     * @return array<int, string>
     */
    private function definedClasses(string $prefix = ''): array
    {
        $classes = [];
        foreach (get_declared_classes() as $class) {
            if (($prefix !== '') && (stripos($class, $prefix) !== 0)) {
                continue;
            }
            $classes[] = $class;
        }
        sort($classes);
        return $classes;
    }

    /**
     * @return array<int, string>
     */
    private function definedFunctions(string $prefix = ''): array
    {
        $functions = [];
        foreach (get_defined_functions()['user'] as $function) {
            if (($prefix !== '') && (stripos($function, $prefix) !== 0)) {
                continue;
            }
            $functions[] = $function;
        }
        sort($functions);
        return $functions;
    }

    /**
     * @param Closure|string|array<object|string, string>|object|callable $callable
     * @return array{string, string}
     * @see https://stackoverflow.com/a/68113840/6161354
     */
    private function getCallableName($callable): array
    {
        switch (true) {
            case is_string($callable) && strpos($callable, '::'):
                return ['static', $callable];
            case is_string($callable):
                return ['function', $callable];
            case is_array($callable) && is_object($callable[0]):
                if (is_string($callable[1])) {
                    $method = $callable[1];
                } else {
                    /** @phpstan-ignore-next-line */
                    $method = (string)$callable[1];
                }
                return ['method', get_class($callable[0]) . '->' . $method];
            case is_array($callable):
                if (is_string($callable[1])) {
                    $method = $callable[1];
                } else {
                    /** @phpstan-ignore-next-line */
                    $method = (string)$callable[1];
                }
                return ['static', $callable[0] . '::' . $method];
            case $callable instanceof Closure:
                try {
                    $reflectionClosure = new ReflectionFunction($callable);
                    $closureName = $reflectionClosure->getName();
                    $class = $reflectionClosure->getClosureScopeClass();
                    $className = $class ? $class->getName() : null;
                    return ['closure', $className ? $className . '--' . $closureName : $closureName];
                } catch (ReflectionException $e) {
                    return ['unknown', ''];
                }
            case is_object($callable):
                if (is_callable($callable)) {
                    return ['invokable', get_class($callable)];
                }
                return ['-', get_class($callable)];
            default:
                return ['unknown', ''];
        }
    }
}
