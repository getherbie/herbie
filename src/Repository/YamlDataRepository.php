<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 01.01.19
 * Time: 14:33
 */

declare(strict_types=1);

namespace Herbie\Repository;

use Herbie\Config;
use Herbie\Exception\SystemException;
use Herbie\Yaml;

class YamlDataRepository implements DataRepositoryInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $extensions;

    /**
     * YamlDataRepository constructor.
     * @param Config $config
     * @throws SystemException
     */
    public function __construct(Config $config)
    {
        $path = strval($config['paths']['data']);
        $extensions = $config['fileExtensions']['data']->toArray();

        if (!is_dir($path)) {
            throw SystemException::directoryNotExist($path);
        }
        if (!is_readable($path)) {
            throw SystemException::directoryNotReadable($path);
        }
        $this->path = $path;
        $this->extensions = $extensions;
    }

    /**
     * @param string $name
     * @return array
     */
    public function load(string $name): array
    {
        $dataFiles = $this->scanDataDir();
        $name = strtolower($name);
        if (!isset($dataFiles[$name])) {
            return [];
        }
        return $this->parseDataFile($dataFiles[$name]);
    }

    /**
     * @return array
     */
    public function loadAll(): array
    {
        $data = [];
        foreach ($this->scanDataDir() as $name => $dataFile) {
            $data[$name] = $this->parseDataFile($dataFile);
        }
        return $data;
    }

    /**
     * @return array
     */
    private function scanDataDir(): array
    {
        $dataFiles = [];

        $files = scandir($this->path);
        if ($files === false) {
            return $dataFiles;
        }

        foreach ($files as $file) {
            if (substr($file, 0, 1) === '.') {
                continue;
            }
            $info = pathinfo($file);
            if (!in_array($info['extension'], $this->extensions)) {
                continue;
            }
            $name = strtolower($info['filename']);
            if (!isset($dataFiles[$name])) {
                $dataFiles[$name] = $this->path . '/' . $file;
            }
        }

        return $dataFiles;
    }

    /**
     * @param string $filepath
     * @return array
     */
    private function parseDataFile(string $filepath): array
    {
        $yaml = file_get_contents($filepath);
        return Yaml::parse($yaml);
    }
}
