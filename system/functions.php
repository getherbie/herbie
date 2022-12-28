<?php

declare(strict_types=1);

namespace herbie;

use Closure;
use Composer\InstalledVersions;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

use function gettype;

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
 * @param Throwable $exception
 */
function render_exception(Throwable $exception): string
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
        if (is_string($path)) {
            $message = str_replace($path, '', $message);
        }
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
    if ($list === '' || $delim === '') {
        return [];
    }
    $values = explode($delim, $list, $limit);
    return array_filter(array_map('trim', $values));
}

/**
 * @return array<string, mixed>
 */
function load_php_config(string $path, ?callable $processor = null): array
{
    $data = include $path;
    if ($processor) {
        $data = $processor($data);
    }
    return $data;
}

/**
 * @return array<string, mixed>
 */
function load_plugin_config(string $path, string $pluginLocation, ?callable $processor = null): array
{
    $config = array_merge(
        ['location' => $pluginLocation],
        load_php_config($path, $processor)
    );
    if (!isset($config['apiVersion'])) {
        throw new UnexpectedValueException(sprintf('Required config "apiVersion" is missing in %s', $path));
    }
    if (!isset($config['pluginName'])) {
        throw new UnexpectedValueException(sprintf('Required config "pluginName" is missing in %s', $path));
    }
    if (!isset($config['pluginPath'])) {
        throw new UnexpectedValueException(sprintf('Required config "pluginPath" is missing in %s', $path));
    }
    return $config;
}

/**
 * @return array<string, array<string, mixed>>
 */
function load_plugin_configs(string $pluginDir, string $pluginLocation, ?callable $processor = null): array
{
    $globPattern = "{$pluginDir}/*/config.php";
    $configFiles = glob("{" . $globPattern . "}", GLOB_BRACE);

    if ($configFiles === false) {
        return [];
    }

    $pluginConfigs = [];
    foreach ($configFiles as $configFile) {
        $config = load_plugin_config($configFile, $pluginLocation, $processor);
        /** @var string $pluginName */
        $pluginName = $config['pluginName'];
        $pluginConfigs[$pluginName] = $config;
    }
    return $pluginConfigs;
}

/**
 * @return array<string, array<string, mixed>>
 */
function load_composer_plugin_configs(): array
{
    $installedPackages = InstalledVersions::getInstalledPackagesByType('herbie-plugin');
    $pluginConfigs = [];
    foreach (array_unique($installedPackages) as $pluginKey) {
        $installPath = InstalledVersions::getInstallPath($pluginKey);
        if ($installPath === null) {
            continue;
        }
        $path = realpath($installPath);
        if ($path === false) {
            continue;
        }
        $composerPluginConfigPath = $path . '/config.php';
        if (is_readable($composerPluginConfigPath)) {
            $composerPluginConfig = load_plugin_config($composerPluginConfigPath, 'composer');
            /** @var string $composerPluginName */
            $composerPluginName = $composerPluginConfig['pluginName'];
            $pluginConfigs[$composerPluginName] = $composerPluginConfig;
        }
    }
    return $pluginConfigs;
}

/**
 * @param string|string[] $find
 * @param string|string[] $replace
 * @param array|scalar $array
 * @return array|scalar
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
    if (($pos = strpos($requestUri, '?')) !== false) {
        $requestUri = substr($requestUri, 0, $pos);
    }

    $mimeTypes = [
        'css' => 'text/css',
        'gif' => 'image/gif',
        'ico' => 'image/vnd.microsoft.icon',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'js' => 'text/javascript',
        'png' => 'image/png',
        'svg' => 'image/svg+xml'
    ];

    $extensions = implode('|', array_keys($mimeTypes));
    $regex = '/\.(?:' . $extensions . ')$/';

    if (preg_match($regex, $requestUri)) {
        $requestedAbsoluteFile = dirname($file) . $requestUri;
        $extension = pathinfo($requestedAbsoluteFile, PATHINFO_EXTENSION);
        header('Content-Type: ' . ($mimeTypes[$extension] ?? 'text/plain'));
        if (!is_readable($requestedAbsoluteFile)) {
            http_response_code(404);
            exit;
        }
        readfile($requestedAbsoluteFile);
        exit;
    }
}

/**
 * @return array<int, string>
 */
function defined_classes(string $prefix = ''): array
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
 * @return array<string, string>
 */
function defined_constants(string $prefix = ''): array
{
    $constants = [];
    foreach (get_defined_constants() as $key => $value) {
        if (($prefix !== '') && (stripos($key, $prefix) !== 0)) {
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
function get_callable_name($callable): array
{
    switch (true) {
        case is_string($callable) && strpos($callable, '::'):
            return ['static', $callable];
        case is_string($callable):
            return ['function', $callable];
        case is_array($callable) && is_object($callable[0]):
            return ['method', get_class($callable[0]) . '->' . $callable[1]];
        case is_array($callable):
            return ['static', $callable[0] . '::' . $callable[1]];
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

/**
 * @param class-string<PluginInterface> $pluginClassName
 * @return array<int, object>
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_constructor_params_to_inject(string $pluginClassName, ContainerInterface $container): array
{
    $reflectedClass = new ReflectionClass($pluginClassName);
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

function file_mtime(string $path): int
{
    $timestamp = filemtime($path);
    if ($timestamp === false) {
        return 0;
    }
    return $timestamp;
}


function file_read(string $path): string
{
    // see Symfony/Component/Finder/SplFileInfo.php
    $error = '';
    set_error_handler(function (int $type, string $msg) use (&$error): bool {
        $error = $msg;
        return true;
    });

    try {
        $content = file_get_contents($path);
    } finally {
        restore_error_handler();
    }

    if (false === $content) {
        throw new RuntimeException($error);
    }

    return $content;
}

function file_size(string $path): int
{
    $size = filesize($path);
    if ($size === false) {
        return 0;
    }
    return $size;
}

function time_from_string(string $datetime): int
{
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return 0;
    }
    return $timestamp;
}

function time_format(string $format, ?int $timestamp = null): string
{
    $timestamp = $timestamp ?? time();
    $formatted = strftime($format, $timestamp);
    if ($formatted === false) {
        return '';
    }
    return $formatted;
}

function date_format(string $format, ?int $timestamp = null): string
{
    $timestamp = $timestamp ?? time();
    return date($format, $timestamp);
}

/**
 * @param int|string $value
 */
function is_digit($value): bool
{
    if (is_int($value)) {
        return true;
    }
    if (!is_string($value)) {
        return false;
    }
    return $value === (string)(int)$value;
}

/**
 * @param int|string $value
 */
function is_natural($value, bool $includingZero = false): bool
{
    $compare = 0;
    if ($includingZero === true) {
        $compare = -1;
    }
    if (is_digit($value) && (int)$value > $compare) {
        return true;
    }
    return false;
}

function array_is_assoc(array $array): bool
{
    $keys = array_keys($array);
    return array_keys($keys) !== $keys;
}

function get_type(mixed $value): string
{
    $type = gettype($value);
    // for historical reasons "double" is returned in case of a float, and not simply "float"
    // see https://www.php.net/manual/en/function.gettype
    if ($type === 'double') {
        $type = 'float';
    }
    return $type;
}
