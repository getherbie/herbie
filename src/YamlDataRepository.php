<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

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
     * @param Configuration $config
     * @throws SystemException
     */
    public function __construct(Configuration $config)
    {
        $path = strval($config['paths']['data']);
        $extensions = explode_list($config['fileExtensions']['data']);

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
