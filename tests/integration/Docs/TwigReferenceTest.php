<?php

declare(strict_types=1);

namespace herbie\tests\integration\Docs;

use Codeception\Test\Unit;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\TwigCallableInterface;
use UnitTester;

final class TwigReferenceTest extends Unit
{
    protected UnitTester $tester;

    private ?Environment $environment = null;

    public function testDocumentedTwigNamesMatchTheRegisteredNames(): void
    {
        $failures = [];
        foreach (['functions', 'filters', 'tests'] as $kind) {
            $documented = array_column($this->reference($kind), 'name');
            $registered = array_keys($this->registered($kind));
            sort($documented);
            sort($registered);
            if ($documented !== $registered) {
                $failures[] = sprintf(
                    'Twig %s: documented only: %s; registered only: %s',
                    $kind,
                    implode(', ', array_diff($documented, $registered)) ?: '-',
                    implode(', ', array_diff($registered, $documented)) ?: '-',
                );
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function testDocumentedTwigFunctionParametersMatchTheirSignatures(): void
    {
        $registered = $this->registered('functions');
        $failures = [];

        foreach ($this->reference('functions') as $entry) {
            $name = $entry['name'];
            $function = $registered[$name] ?? null;
            if ($function === null) {
                continue;
            }

            $callable = $function->getCallable();
            if ($callable === null) {
                $failures[] = sprintf('%s: no callable', $name);
                continue;
            }
            $reflection = is_array($callable)
                ? new ReflectionMethod($callable[0], $callable[1])
                : new ReflectionFunction($callable);
            $parameters = $reflection->getParameters();
            if ($function->needsContext()) {
                array_shift($parameters);
            }

            $documented = $entry['params'] ?? [];
            if (count($documented) !== count($parameters)) {
                $failures[] = sprintf(
                    '%s: %d documented parameters, %d actual parameters',
                    $name,
                    count($documented),
                    count($parameters),
                );
                continue;
            }

            foreach ($parameters as $index => $parameter) {
                $failure = $this->compareParameter($name, $documented[$index], $parameter);
                if ($failure !== null) {
                    $failures[] = $failure;
                }
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    /**
     * @param array<string, mixed> $documented
     */
    private function compareParameter(string $function, array $documented, ReflectionParameter $parameter): ?string
    {
        if ($documented['name'] !== $parameter->getName()) {
            return sprintf(
                '%s: documented name "%s", actual name "%s"',
                $function,
                $documented['name'],
                $parameter->getName(),
            );
        }

        $required = ($documented['required'] ?? false) === true;
        $default = $documented['default'] ?? null;
        if ($required && !$parameter->isOptional()) {
            return null;
        }
        if (!$parameter->isOptional()) {
            return sprintf('%s: %s is documented as optional but is required', $function, $parameter->getName());
        }
        if ($required && $default === null) {
            return sprintf('%s: %s is documented as required but is optional without a default', $function, $parameter->getName());
        }
        if ($default === null || $this->normalize($default) !== $this->normalize($parameter->getDefaultValue())) {
            return sprintf(
                '%s: %s has documented default %s, actual default %s',
                $function,
                $parameter->getName(),
                var_export($default, true),
                var_export($parameter->getDefaultValue(), true),
            );
        }

        return null;
    }

    private function normalize(mixed $value): string
    {
        if (is_string($value)) {
            $quote = $value[0] ?? '';
            return strlen($value) > 1 && ($quote === '"' || $quote === "'") && str_ends_with($value, $quote)
                ? substr($value, 1, -1)
                : $value;
        }
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return $value === [] ? '[]' : (string)json_encode($value);
        }

        return (string)$value;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function reference(string $kind): array
    {
        $path = dirname(__DIR__, 3) . '/website/site/data/twig_' . $kind . '.yml';
        $data = Yaml::parseFile($path);
        if (!is_array($data)) {
            throw new RuntimeException(sprintf('Invalid Twig reference data in %s', $path));
        }

        return $data;
    }

    /**
     * @return array<string, TwigCallableInterface>
     */
    private function registered(string $kind): array
    {
        $environment = $this->environment();
        $callables = match ($kind) {
            'functions' => $environment->getFunctions(),
            'filters' => $environment->getFilters(),
            'tests' => $environment->getTests(),
        };

        // Plugin callables are added directly to the environment and belong to no extension.
        $builtinNames = [];
        $herbieNames = [];
        foreach ($environment->getExtensions() as $extension) {
            $extensionCallables = match ($kind) {
                'functions' => $extension->getFunctions(),
                'filters' => $extension->getFilters(),
                'tests' => $extension->getTests(),
            };
            foreach ($extensionCallables as $callable) {
                if (str_starts_with($extension::class, 'Twig\\')) {
                    $builtinNames[$callable->getName()] = true;
                } else {
                    $herbieNames[$callable->getName()] = true;
                }
            }
        }

        $items = [];
        foreach ($callables as $callable) {
            $name = $callable->getName();
            if (isset($herbieNames[$name]) || !isset($builtinNames[$name])) {
                $items[$name] = $callable;
            }
        }

        return $items;
    }

    private function environment(): Environment
    {
        return $this->environment ??= $this->tester->initTwigRenderer(
            dirname(__DIR__, 3),
            dirname(__DIR__, 1) . '/Fixtures/docs-site',
        )->getTwigEnvironment();
    }
}
