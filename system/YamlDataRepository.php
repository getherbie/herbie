<?php

declare(strict_types=1);

namespace herbie;

use Symfony\Component\Finder\Finder;

class YamlDataRepository implements DataRepositoryInterface
{
    protected string $path;

    /** @var string[] */
    protected array $extensions;

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

    #[\Override]
    /** @return array<string, mixed> */
    public function load(string $name): array
    {
        $files = $this->scanDir();
        $name = strtolower($name);
        if (!isset($files[$name])) {
            return [];
        }
        return $this->parseData($files[$name]);
    }

    /** @return array<string, string> */
    protected function scanDir(): array
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

    /** @return array<mixed, mixed> */
    protected function parseData(string $contents): array
    {
        return Yaml::parse($contents);
    }

    #[\Override]
    /** @return array<string, mixed> */
    public function loadAll(): array
    {
        $data = [];
        foreach ($this->scanDir() as $basename => $contents) {
            $data[$basename] = $this->parseData($contents);
        }
        return $data;
    }

    protected function getFinder(): Finder
    {
        $patterns = $this->getPatterns();
        return (new Finder())->files()->name($patterns)->in($this->path)->sortByName();
    }

    /** @return string[] */
    protected function getPatterns(): array
    {
        return array_map(function (string $extension) {
            return '*.' . $extension;
        }, $this->extensions);
    }
}
