<?php

declare(strict_types=1);

namespace herbie;

use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;

final readonly class FileCache implements CacheInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        if (empty($path)) {
            throw new CacheInvalidArgumentException('Path was not provided');
        }

        if (!is_dir($path) && !@mkdir($path, 0755, true) && !is_dir($path)) {
            throw new CacheInvalidArgumentException('Provided path directory does not exist and/or could not be created');
        }

        if (!is_writable($path)) {
            throw new CacheInvalidArgumentException('Provided path is not a writable directory');
        }

        $this->path = $path;
    }

    #[\Override]
    public function clear(): bool
    {
        $filenames = glob($this->path . DIRECTORY_SEPARATOR . '*');
        if ($filenames === false) {
            return false;
        }

        $result = true;
        foreach ($filenames as $filename) {
            if (is_file($filename) && !unlink($filename)) {
                $result = false;
            }
        }

        return $result;
    }

    #[\Override]
    public function has(string $key): bool
    {
        return file_exists($this->getFilename($this->validateKey($key)));
    }

    protected function getFilename(string $key): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $key;
    }

    private function validateKey(mixed $key): string
    {
        // PSR-16 minimum: [A-Za-z0-9_.], 1-64 characters.
        if (!is_string($key) || preg_match('/^[A-Za-z0-9_.]{1,64}$/D', $key) !== 1 || $key === '.' || $key === '..') {
            throw new CacheInvalidArgumentException(
                'Cache key must be a string of 1 to 64 characters from [A-Za-z0-9_.], excluding "." and "..".',
            );
        }

        return $key;
    }

    /**
     * @return string[]
     */
    private function validateKeys(mixed $keys): array
    {
        if (!is_array($keys) && !$keys instanceof \Traversable) {
            throw new CacheInvalidArgumentException('Cache keys must be an array or Traversable.');
        }

        $result = [];
        foreach ($keys as $key) {
            $result[] = $this->validateKey($key);
        }

        return $result;
    }

    #[\Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $multiple = [];
        foreach ($this->validateKeys($keys) as $key) {
            $multiple[$key] = $this->get($key, $default);
        }

        return $multiple;
    }

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->validateKey($key);
        $cacheFile = $this->getFilename($key);

        if (!file_exists($cacheFile)) {
            return $default;
        }

        $contents = (string)file_get_contents($cacheFile);
        $file = $this->unserialize($contents);

        if (!is_array($file) || ($file['key'] ?? null) !== $key) {
            return $default;
        }

        if ($file['ttl'] != 0 && time() - $file['ctime'] > $file['ttl']) {
            $this->delete($key);

            return $default;
        }

        return $file['value'];
    }

    #[\Override]
    public function delete(string $key): bool
    {
        $filename = $this->getFilename($this->validateKey($key));

        if (file_exists($filename)) {
            return unlink($filename);
        }

        return false;
    }

    /**
     * @return mixed
     */
    protected function unserialize(string $data, array $options = [])
    {
        // Restrict deserialization to scalar/array values only to mitigate
        // object injection via tampered cache files (security hardening).
        if (!array_key_exists('allowed_classes', $options)) {
            $options['allowed_classes'] = false;
        }

        return unserialize($data, $options);
    }

    #[\Override]
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        $result = true;
        foreach ($values as $key => $value) {
            if (!$this->set($this->validateKey($key), $value, $ttl)) {
                $result = false;
            }
        }

        return $result;
    }

    #[\Override]
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $key = $this->validateKey($key);
        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTimeImmutable())->add($ttl)->getTimestamp() - time();
        }

        $file = [
            'key' => $key,
            'value' => $value,
            'ttl' => $ttl,
            'ctime' => time(),
        ];

        $filename = $this->getFilename($key);
        $tempFile = $filename . '.' . bin2hex(random_bytes(8)) . '.tmp';

        try {
            if (file_put_contents($tempFile, serialize($file)) === false) {
                return false;
            }

            return rename($tempFile, $filename);
        } finally {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    #[\Override]
    public function deleteMultiple(iterable $keys): bool
    {
        $result = true;
        foreach ($this->validateKeys($keys) as $key) {
            if (!$this->delete($key)) {
                $result = false;
            }
        }

        return $result;
    }
}
