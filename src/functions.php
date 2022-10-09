<?php

declare(strict_types=1);

namespace herbie;

use Closure;
use Composer\InstalledVersions;

function camelize(string $input, string $separator = '_'): string
{
    return str_replace($separator, '', ucwords($input, $separator));
}

/**
 * @throws SystemException
 */
function normalize_path(string $path): string
{
    $realpath = realpath($path);
    if ($realpath === false) {
        $message = sprintf('Could not normalize path "%s"', $path);
        throw SystemException::serverError($message);
    }
    return rtrim($realpath, '/');
}

/**
 * @param \Throwable $exception
 */
function render_exception(\Throwable $exception): string
{
    if (HERBIE_DEBUG) {
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

function explode_list(string $list, string $delim = ','): array
{
    $list = trim($list);
    if (strlen($list) === 0) {
        return [];
    }
    $values = explode($delim, $list);
    return array_map('trim', $values);
}

function load_php_config(string $path, ?callable $processor = null): array
{
    $data = include($path);
    if ($processor) {
        $data = $processor($data);
    }
    return $data;
}

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
 * @param string|array $find
 * @param string|array $replace
 * @param array|scalar $array
 * @return array|scalar
 */
function recursive_array_replace($find, $replace, $array)
{
    if (!is_array($array)) {
        if (is_string($array)) {
            return str_replace($find, $replace, $array);
        }
        return $array;
    }

    $newArray = [];

    foreach ($array as $key => $value) {
        $newArray[$key] = recursive_array_replace($find, $replace, $value);
    }

    return $newArray;
}

function handle_internal_webserver_assets($file): void
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
 * @phpstan-return array<int,string>
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
 * @phpstan-return array<string,string>
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
 * @phpstan-return array<int,string>
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
            return ['closure', null];
        case is_object($callable):
            return ['invokable', get_class($callable)];
        default:
            return ['unknown', null];
    }
}
