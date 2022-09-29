<?php

/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

final class JsonDataRepository implements DataRepositoryInterface
{
    private string $path;

    private array $extensions;

    /**
     * YamlDataRepository constructor.
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->extensions = ['json'];
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

    /**
     * @throws SystemException
     */
    private function scanDataDir(): array
    {
        $this->validatePath();

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
     * @throws SystemException
     */
    private function validatePath(): void
    {
        if (!is_dir($this->path)) {
            throw SystemException::directoryNotExist($this->path);
        }
        if (!is_readable($this->path)) {
            throw SystemException::directoryNotReadable($this->path);
        }
    }

    private function parseDataFile(string $filepath): array
    {
        return json_decode(file_get_contents($filepath), true);
    }
}
