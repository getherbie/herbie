<?php

declare(strict_types=1);

namespace herbie;

use Closure;
use Composer\InstalledVersions;
use Psr\Container\ContainerInterface;
use ReflectionFunction;
use ReflectionNamedType;

function str_camelize(string $input, string $separator = '_'): string
{
    return str_replace($separator, '', ucwords($input, $separator));
}

/**
 * @throws SystemException
 */
function path_normalize(string $path): string
{
    $realpath = realpath($path);
    if ($realpath === false) {
        $message = sprintf('Could not normalize path "%s"', $path);
        throw SystemException::serverError($message);
    }
    return str_trailing_slash($realpath);
}

/**
 * Prepends a leading slash.
 *
 * @since 2.0.0
 */
function str_leading_slash(string $string): string
{
    return '/' . str_unleading_slash($string);
}

/**
 * Appends a trailing slash.
 *
 * @since 2.0.0
 */
function str_trailing_slash(string $string): string
{
    return str_untrailing_slash($string) . '/';
}

/**
 * Removes leading forward slashes and backslashes if they exist.
 *
 * @since 2.0.0
 */
function str_unleading_slash(string $string): string
{
    return ltrim($string, '/\\');
}

/**
 * Removes trailing forward slashes and backslashes if they exist.
 *
 * @since 2.0.0
 */
function str_untrailing_slash(string $string): string
{
    return rtrim($string, '/\\');
}

/**
 * @param \Throwable $exception
 */
function render_exception(\Throwable $exception): string
{
    if (Application::isDebug()) {
        $format = "%s [%s] in %s on line %s\n\n%s\n\nStack trace:\n%s";
        $message = sprintf(
            $format,
            get_class($exception),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getMessage(),
            strip_tags($exception->getTraceAsString())
        );

        // remove path
        $path = realpath(__DIR__ . '/../../');
        $message = str_replace($path, '', $message);
        return sprintf('<pre class="error error--exception error--debug">%s</pre>', $message);
    }

    $format = '%s';
    $message = sprintf($format, $exception->getMessage());
    return sprintf('<pre class="error error--exception">%s</pre>', $message);
}

/**
 * Split a string by a delimiter.
 *
 * Unlike the original PHP explode function, an empty array is returned, if string is empty.
 * Additionally, the returned array items are trimmed and empty items are filtered.
 *
 * @return string[]
 */
function str_explode_filtered(string $list, string $delim, int $limit = PHP_INT_MAX): array
{
    $list = trim($list);
    $delim = trim($delim);
    if ((strlen($list) === 0) || (strlen($delim) === 0)) {
        return [];
    }
    $values = explode($delim, $list, $limit);
    return array_filter(array_map('trim', $values));
}

/**
 * @return array<int|string, mixed>
 */
function load_php_config(string $path, ?callable $processor = null): array
{
    $data = include($path);
    if ($processor) {
        $data = $processor($data);
    }
    return $data;
}

/**
 * @return array<int|string, mixed>
 */
function load_plugin_config(string $path, string $pluginLocation, ?callable $processor = null): array
{
    $config = array_merge(
        ['location' => $pluginLocation],
        load_php_config($path, $processor)
    );
    if (!isset($config['apiVersion'])) {
        throw new \UnexpectedValueException(sprintf('Required config "apiVersion" is missing in %s', $path));
    }
    if (!isset($config['pluginName'])) {
        throw new \UnexpectedValueException(sprintf('Required config "pluginName" is missing in %s', $path));
    }
    if (!isset($config['pluginPath'])) {
        throw new \UnexpectedValueException(sprintf('Required config "pluginPath" is missing in %s', $path));
    }
    return $config;
}

/**
 * @return array<string, array<int|string, mixed>>
 */
function load_plugin_configs(string $pluginDir, string $pluginLocation, ?callable $processor = null): array
{
    $globPattern = "{$pluginDir}/*/config.php";
    $configFiles = glob("{" . $globPattern . "}", GLOB_BRACE);

    $pluginConfigs = [];
    foreach ($configFiles as $configFile) {
        $config = load_plugin_config($configFile, $pluginLocation, $processor);
        $pluginName = $config['pluginName'];
        $pluginConfigs[$pluginName] = $config;
    }
    return $pluginConfigs;
}

/**
 * @return array<string, array<int|string, mixed>>
 */
function load_composer_plugin_configs(): array
{
    $installedPackages = InstalledVersions::getInstalledPackagesByType('herbie-plugin');
    $pluginConfigs = [];
    foreach (array_unique($installedPackages) as $pluginKey) {
        $path = realpath(InstalledVersions::getInstallPath($pluginKey));
        $composerPluginConfigPath = $path . '/config.php';
        if (is_readable($composerPluginConfigPath)) {
            $composerPluginConfig = load_plugin_config($composerPluginConfigPath, 'composer');
            $composerPluginName = $composerPluginConfig['pluginName'];
            $pluginConfigs[$composerPluginName] = $composerPluginConfig;
        }
    }
    return $pluginConfigs;
}

/**
 * @param string|string[] $find
 * @param string|string[] $replace
 * @param array<int|string, mixed>|scalar $array
 * @return array<string, mixed>|scalar
 */
function recursive_array_replace($find, $replace, $array)
{
    if (is_string($array)) {
        return str_replace($find, $replace, $array);
    }

    if (!is_array($array)) {
        return $array;
    }

    $newArray = [];

    foreach ($array as $key => $value) {
        $newArray[$key] = recursive_array_replace($find, $replace, $value);
    }

    return $newArray;
}

function handle_internal_webserver_assets(string $file): void
{
    if (php_sapi_name() !== 'cli-server') {
        return;
    }

    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    $mimeTypes = [
        'css' => 'text/css',
        'gif' => 'image/gif',
        'ico' => 'image/vnd.microsoft.icon',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'js' => 'text/javascript',
        'png' => 'image/png',
    ];

    $extensions = implode('|', array_keys($mimeTypes));
    $regex = '/\.(?:' . $extensions . ')$/';

    if (preg_match($regex, $requestUri)) {
        $requestedAbsoluteFile = dirname($file) . $requestUri;
        $extension = pathinfo($requestedAbsoluteFile, PATHINFO_EXTENSION);
        header('Content-Type: ' . ($mimeTypes[$extension] ?? 'text/plain'));
        readfile($requestedAbsoluteFile);
        exit;
    }
}

/**
 * @see https://stackoverflow.com/questions/7153000/get-class-name-from-file
 */
function get_fully_qualified_class_name(string $file): string
{
    $tokens = token_get_all(file_get_contents($file));
    $tokensCount = count($tokens);

    $className = '';
    $namespaceName = '';

    for ($i = 0; $i < $tokensCount; $i++) {
        // namespace token
        if (is_array($tokens[$i]) && (token_name($tokens[$i][0]) === 'T_NAMESPACE')) {
            for ($j = $i + 1; $j < $tokensCount; $j++) {
                if ($tokens[$j][0] === ';') {
                    break;
                }
                $tokenName = token_name($tokens[$j][0]);
                if ($tokenName === 'T_WHITESPACE') {
                    continue;
                }
                if (in_array($tokenName, ['T_NAME_QUALIFIED', 'T_NS_SEPARATOR', 'T_STRING'])) {
                    $namespaceName .= $tokens[$j][1];
                }
            }
        }
        // class token
        if (is_array($tokens[$i]) && (token_name($tokens[$i][0]) === 'T_CLASS')) {
            for ($j = $i + 1; $j < count($tokens); $j++) {
                $tokenName = token_name($tokens[$j][0]);
                if ($tokenName === 'T_WHITESPACE') {
                    continue;
                }
                if ($tokenName === 'T_STRING') {
                    $className = $tokens[$j][1];
                    break;
                }
            }
        }
    }

    if (strlen($namespaceName) === 0) {
        return $className;
    }

    return $namespaceName . '\\' . $className;
}

/**
 * @return array<int, string>
 */
function defined_classes(string $prefix = ''): array
{
    $classes = [];
    foreach (get_declared_classes() as $class) {
        if ((strlen($prefix) > 0) && (stripos($class, $prefix) !== 0)) {
            continue;
        }
        $classes[] = $class;
    }
    sort($classes);
    return $classes;
}

/**
 * @return array<string, string>
 */
function defined_constants(string $prefix = ''): array
{
    $constants = [];
    foreach (get_defined_constants() as $key => $value) {
        if ((strlen($prefix) > 0) && (stripos($key, $prefix) !== 0)) {
            continue;
        }
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $constants[$key] = $value;
    }
    ksort($constants);
    return $constants;
}

/**
 * @return array<int, string>
 */
function defined_functions(string $prefix = ''): array
{
    $functions = [];
    foreach (get_defined_functions()['user'] as $function) {
        if ((strlen($prefix) > 0) && (stripos($function, $prefix) !== 0)) {
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
function get_callable_name($callable): array
{
    switch (true) {
        case is_string($callable) && strpos($callable, '::'):
            return ['static', $callable];
        case is_string($callable):
            return ['function', $callable];
        case is_array($callable) && is_object($callable[0]):
            return ['method', get_class($callable[0])  . '->' . $callable[1]];
        case is_array($callable):
            return ['static', $callable[0]  . '::' . $callable[1]];
        case $callable instanceof Closure:
            try {
                $reflectionClosure = new ReflectionFunction($callable);
                $closureName = $reflectionClosure->getName();
                $class = $reflectionClosure->getClosureScopeClass();
                $className = $class ? $class->getName() : null;
                return ['closure', $className ? $className . '--' . $closureName : $closureName];
            } catch (\ReflectionException $e) {
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

/**
 * @return array<int, object>
 * @throws \Psr\Container\ContainerExceptionInterface
 * @throws \Psr\Container\NotFoundExceptionInterface
 * @throws \ReflectionException
 */
function get_constructor_params_to_inject(string $pluginClassName, ContainerInterface $container): array
{
    $reflectedClass = new \ReflectionClass($pluginClassName);
    $constructor = $reflectedClass->getConstructor();
    if (!$constructor) {
        return [];
    }

    $constructorParams = [];
    foreach ($constructor->getParameters() as $param) {
        /** @var ReflectionNamedType|null $type */
        $type = $param->getType();
        if ($type === null) {
            continue;
        }
        $classNameToInject = $type->getName();
        if (in_array($classNameToInject, ['string'])) {
            continue;
        }
        $constructorParams[] = $container->get($classNameToInject);
    }
    return $constructorParams;
}
