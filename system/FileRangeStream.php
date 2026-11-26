<?php

declare(strict_types=1);

namespace herbie;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

/**
 * A read-only PSR-7 stream for a byte range of a file, e.g. for a response with status 206.
 */
final class FileRangeStream implements StreamInterface
{
    /** @var resource|null */
    private $handle;

    /** Position relative to the start of the range */
    private int $position = 0;

    public function __construct(string $filepath, private readonly int $offset, private readonly int $length)
    {
        $handle = fopen($filepath, 'rb');
        if ($handle === false) {
            throw new RuntimeException(sprintf('Unable to open file "%s".', $filepath));
        }
        $this->handle = $handle;
        fseek($handle, $this->offset);
    }

    public function __destruct()
    {
        $this->close();
    }

    #[\Override]
    public function __toString(): string
    {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (Throwable) {
            return '';
        }
    }

    #[\Override]
    public function close(): void
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
        $this->handle = null;
    }

    #[\Override]
    public function detach()
    {
        $handle = $this->handle;
        $this->handle = null;
        return $handle;
    }

    #[\Override]
    public function getSize(): int
    {
        return $this->length;
    }

    #[\Override]
    public function tell(): int
    {
        return $this->position;
    }

    #[\Override]
    public function eof(): bool
    {
        return $this->handle === null || $this->position >= $this->length;
    }

    #[\Override]
    public function isSeekable(): bool
    {
        return $this->handle !== null;
    }

    #[\Override]
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if ($this->handle === null) {
            throw new RuntimeException('Stream is detached.');
        }
        $target = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => $this->length + $offset,
            default => throw new RuntimeException('Invalid whence value.'),
        };
        if ($target < 0 || $target > $this->length) {
            throw new RuntimeException('Cannot seek outside of the range.');
        }
        fseek($this->handle, $this->offset + $target);
        $this->position = $target;
    }

    #[\Override]
    public function rewind(): void
    {
        $this->seek(0);
    }

    #[\Override]
    public function isWritable(): bool
    {
        return false;
    }

    #[\Override]
    public function write(string $string): int
    {
        throw new RuntimeException('Stream is not writable.');
    }

    #[\Override]
    public function isReadable(): bool
    {
        return $this->handle !== null;
    }

    #[\Override]
    public function read(int $length): string
    {
        if ($this->handle === null) {
            throw new RuntimeException('Stream is detached.');
        }
        $remaining = $this->length - $this->position;
        if ($length <= 0 || $remaining <= 0) {
            return '';
        }
        $data = fread($this->handle, min($length, $remaining));
        if ($data === false) {
            throw new RuntimeException('Unable to read from stream.');
        }
        $this->position += strlen($data);
        return $data;
    }

    #[\Override]
    public function getContents(): string
    {
        $contents = '';
        while (!$this->eof()) {
            $chunk = $this->read(8192);
            if ($chunk === '') {
                break;
            }
            $contents .= $chunk;
        }
        return $contents;
    }

    #[\Override]
    public function getMetadata(?string $key = null)
    {
        return $key === null ? [] : null;
    }
}
