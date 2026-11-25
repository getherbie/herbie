<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\FileRangeStream;
use RuntimeException;

final class FileRangeStreamTest extends Unit
{
    private string $file;

    protected function _before(): void
    {
        $this->file = sys_get_temp_dir() . '/herbie-range-stream-' . bin2hex(random_bytes(4));
        file_put_contents($this->file, 'abcdefghijklm');
    }

    protected function _after(): void
    {
        unlink($this->file);
    }

    public function testReadsOnlyTheRange(): void
    {
        $stream = new FileRangeStream($this->file, 3, 4);

        $this->assertSame(4, $stream->getSize());
        $this->assertSame('defg', (string)$stream);
        $this->assertTrue($stream->eof());
    }

    public function testReadsInChunksAndStopsAtTheEndOfTheRange(): void
    {
        $stream = new FileRangeStream($this->file, 3, 4);

        $this->assertFalse($stream->eof());
        $this->assertSame('de', $stream->read(2));
        $this->assertSame(2, $stream->tell());
        $this->assertSame('fg', $stream->read(100));
        $this->assertSame('', $stream->read(1));
        $this->assertTrue($stream->eof());
    }

    public function testSeekIsRelativeToTheRange(): void
    {
        $stream = new FileRangeStream($this->file, 3, 4);

        $stream->seek(2);
        $this->assertSame('fg', $stream->getContents());

        $stream->rewind();
        $this->assertSame('d', $stream->read(1));

        $stream->seek(-1, SEEK_END);
        $this->assertSame('g', $stream->getContents());

        $stream->seek(1);
        $stream->seek(1, SEEK_CUR);
        $this->assertSame('f', $stream->read(1));
    }

    public function testSeekOutsideTheRangeFails(): void
    {
        $stream = new FileRangeStream($this->file, 3, 4);

        $this->expectException(RuntimeException::class);

        $stream->seek(5);
    }

    public function testStreamIsReadOnly(): void
    {
        $stream = new FileRangeStream($this->file, 0, 4);

        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isSeekable());
        $this->assertFalse($stream->isWritable());

        $this->expectException(RuntimeException::class);

        $stream->write('x');
    }

    public function testClosedStreamCannotBeRead(): void
    {
        $stream = new FileRangeStream($this->file, 0, 4);
        $stream->close();

        $this->assertTrue($stream->eof());
        $this->assertFalse($stream->isReadable());
        $this->assertSame('', (string)$stream);

        $this->expectException(RuntimeException::class);

        $stream->read(1);
    }

    public function testMissingFileThrows(): void
    {
        $this->expectException(RuntimeException::class);

        @new FileRangeStream($this->file . '-missing', 0, 4);
    }
}
