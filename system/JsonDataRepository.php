<?php

declare(strict_types=1);

namespace herbie;

final class JsonDataRepository extends YamlDataRepository implements DataRepositoryInterface
{
    /**
     * YamlDataRepository constructor.
     */
    public function __construct(string $path)
    {
        $this->extensions = ['json'];
        $this->path = $path;
    }

    protected function parseData(string $contents): array
    {
        return json_decode($contents, true);
    }
}
