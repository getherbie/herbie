<?php

declare(strict_types=1);

namespace herbie;

use Symfony\Component\Finder\Finder;

final class YamlDataRepository implements DataRepositoryInterface
{
    private string $path;

    /** @var string[] */
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

    private function scanDataDir(): array
    {
        static $data;
        if ($data === null) {
            $data = [];
            foreach ($this->getFinder() as $file) {
                $basename = $file->getBasename('.' . $file->getExtension()); // kind of weird
                $data[$basename] = $file->getContents();
            }
        }
        return $data;
    }

    private function parseDataFile(string $contents): array
    {
        return Yaml::parse($contents);
    }

    public function loadAll(): array
    {
        $data = [];
        foreach ($this->scanDataDir() as $basename => $contents) {
            $data[$basename] = $this->parseDataFile($contents);
        }
        return $data;
    }

    private function getFinder(): Finder
    {
        $patterns = $this->getPatterns();
        return (new Finder())->files()->name($patterns)->in($this->path);
    }

    private function getPatterns(): array
    {
        return array_map(function (string $extension) {
            return '*.' . $extension;
        }, $this->extensions);
    }
}
