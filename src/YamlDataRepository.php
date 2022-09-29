<?php

/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

final class YamlDataRepository implements DataRepositoryInterface
{
    private string $path;

    private array $extensions;

    /**
     * YamlDataRepository constructor.
     * @throws SystemException
     */
    public function __construct(string $path)
    {
        if (!is_dir($path)) {
            throw SystemException::directoryNotExist($path);
        }
        if (!is_readable($path)) {
            throw SystemException::directoryNotReadable($path);
        }
        $this->path = $path;
        $this->extensions = ['yml', 'yaml'];
    }

    public function load(string $name): array
    {
        $dataFiles = $this->scanDataDir();
        $name = strtolower($name);
        if (!isset($dataFiles[$name])) {
            return [];
        }
        return $this->parseDataFile($dataFiles[$name]);
    }

    public function loadAll(): array
    {
        $data = [];
        foreach ($this->scanDataDir() as $name => $dataFile) {
            $data[$name] = $this->parseDataFile($dataFile);
        }
        return $data;
    }

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

    private function parseDataFile(string $filepath): array
    {
        $yaml = file_get_contents($filepath);
        return Yaml::parse($yaml);
    }
}
