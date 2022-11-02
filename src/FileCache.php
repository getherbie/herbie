<?php

declare(strict_types=1);

namespace herbie;

use Psr\SimpleCache\CacheInterface;

final class FileCache implements CacheInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        if (empty($path)) {
            throw new \InvalidArgumentException('Path was not provided');
        }

        if (!is_dir($path) && !@mkdir($path, 0644, true) && !is_dir($path)) {
            throw new \InvalidArgumentException('Provided path directory does not exist and/or could not be created');
        }

        if (!is_writable($path)) {
            throw new \InvalidArgumentException('Provided path is not a writable directory');
        }

        $this->path = $path; // here we have a valid and existing path
    }

    public function get($key, $default = null)
    {
        if (empty($key)) {
            return $default;
        }

        $cacheFile = $this->getFilename($key);

        if (!file_exists($cacheFile)) {
            return $default;
        }

        $file = json_decode((string)file_get_contents($cacheFile), true);

        if (!is_array($file) || $file['key'] !== $key) {
            return $default;
        }

        if ($file['ttl'] != 0 && time() - $file['ctime'] > $file['ttl']) {
            $this->delete($key);

            return $default;
        }

        return $this->unserialize($file['value']) ?? $default;
    }

    public function set($key, $value, $ttl = null)
    {
        if (empty($key)) {
            return false;
        }

        $file = [
            'key' => $key,
            'value' => $this->serialize($value),
            'ttl' => $ttl,
            'ctime' => time(),
        ];

        return (bool)file_put_contents($this->getFilename($key), json_encode($file));
    }

    public function delete($key)
    {
        $filename = $this->getFilename($key);

        if (file_exists($filename)) {
            return unlink($filename);
        }

        return false;
    }

    public function clear(): bool
    {
        $filenames = glob($this->path . '/*.cache');
        if ($filenames === false) {
            return false;
        }
        foreach ($filenames as $filename) {
            if (file_exists($filename)) {
                unlink($filename);
            }
        }

        return true;
    }

    public function has($key)
    {
        if (empty($key)) {
            return false;
        }

        return file_exists($this->getFilename($key));
    }

    public function getMultiple($keys, $default = null)
    {
        $multiple = [];
        foreach ($keys as $key) {
            $multiple[$key] = $this->get($key, $default);
        }
        return $multiple;
    }

    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple($keys)
    {
        foreach ((array)$keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    protected function getFilename(string $key): string
    {
        return $this->path . DIRECTORY_SEPARATOR . md5($key) . '.cache';
    }

    /**
     * @param mixed $data
     */
    protected function serialize($data): string
    {
        return serialize($data);
    }

    /**
     * @return mixed
     */
    protected function unserialize(string $data, array $options = [])
    {
        return unserialize($data, $options);
    }
}
