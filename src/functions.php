<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

use Composer\InstalledVersions;

/**
 * @param string $input
 * @param string $separator
 * @return string
 */
function camelize(string $input, string $separator = '_'): string
{
    return str_replace($separator, '', ucwords($input, $separator));
}

/**
 * @param string $path
 * @return string
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
 * @return string
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

/**
 * @param string $list
 * @param string $delim
 * @return array
 */
function explode_list(string $list, string $delim = ',')
{
    $list = trim($list);
    if (strlen($list) === 0) {
        return [];
    }
    $values = explode($delim, $list);
    $values = array_map('trim', $values);
    return $values;
}

/**
 * @param string $path
 * @param callable|null $processor
 * @return array
 */
function load_php_config(string $path, callable $processor = null): array
{
    $data = include($path);
    if ($processor) {
        $data = $processor($data);
    }
    return $data;
}

/**
 * @param string $path
 * @param callable|null $processor
 * @return array
 */
function load_plugin_config(string $path, callable $processor = null): array
{
    $config = load_php_config($path, $processor);
    if (!isset($config['apiVersion'])) {
        throw new \RuntimeException(sprintf('Required config "apiVersion" is missing in %s', $path));
    }
    if (!isset($config['pluginName'])) {
        throw new \RuntimeException(sprintf('Required config "pluginName" is missing in %s', $path));
    }
    if (!isset($config['pluginPath'])) {
        throw new \RuntimeException(sprintf('Required config "pluginPath" is missing in %s', $path));
    }
    return $config;
}

function load_plugin_configs(string $pluginDir, callable $processor = null): array
{
    $globPattern = "{$pluginDir}/*/config.php";
    $configFiles = glob("{" . $globPattern . "}", GLOB_BRACE);
    
    $pluginConfigs = [];
    foreach ($configFiles as $configFile) {
        $config = load_plugin_config($configFile, $processor);
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
            $composerPluginConfig = load_plugin_config($composerPluginConfigPath);
            $composerPluginName = $composerPluginConfig['pluginName'];
            $pluginConfigs[$composerPluginName] = $composerPluginConfig;
        }
    }
    return $pluginConfigs;
}

/**
 * @param string|array $find
 * @param string|array $replace
 * @param string|array $array
 * @return string|array
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
