<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Alias;
use herbie\Config;
use herbie\plugins\imagine\ImaginePlugin;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use InvalidArgumentException;

final class ImaginePluginTest extends Unit
{
    private string $webDir;
    private string $mediaDir;
    private string $oldCwd;

    protected function _before(): void
    {
        $base = sys_get_temp_dir() . '/herbie-imagine-test-' . uniqid('', true);
        $this->webDir = $base . '/web';
        $this->mediaDir = $base . '/site/media';
        mkdir($this->webDir . '/cache/imagine', 0777, true);
        mkdir($this->mediaDir, 0777, true);

        $image = (new Imagine())->create(new Box(40, 30));
        $image->save($this->mediaDir . '/sample.png');

        $cwd = getcwd();
        $this->assertNotFalse($cwd);
        $this->oldCwd = $cwd;
        $outside = $base . '/outside';
        mkdir($outside, 0777, true);
        chdir($outside);
    }

    protected function _after(): void
    {
        chdir($this->oldCwd);
        $this->deleteDir(dirname($this->webDir));
    }

    public function testRelativeCachePathIsResolvedFromWebDirectory(): void
    {
        $plugin = $this->createPlugin('cache/imagine');

        $markup = $plugin->imagineFilter('sample.png', 'default');

        $expectedUrl = '/cache/imagine/sample-default.png';
        $this->assertSame($expectedUrl, (string)$markup);
        $this->assertFileExists($this->webDir . $expectedUrl);
        $this->assertFileDoesNotExist(dirname($this->webDir) . '/outside/cache/imagine/sample-default.png');
    }

    public function testPublicMediaUrlIsAcceptedAsPath(): void
    {
        $plugin = $this->createPlugin('cache/imagine');

        $markup = $plugin->imagineFilter('/media/sample.png', 'default');

        $this->assertSame('/cache/imagine/sample-default.png', (string)$markup);
    }

    public function testPathWithoutLeadingSlashKeepsBeingRelativeToTheMediaDirectory(): void
    {
        $plugin = $this->createPlugin('cache/imagine');

        // refers to site/media/media/sample.png, which does not exist
        $markup = $plugin->imagineFilter('media/sample.png', 'default');

        $this->assertStringStartsWith('data:image/png;base64,', (string)$markup);
    }

    public function testCustomRelativeCachePathIsResolvedFromWebDirectory(): void
    {
        mkdir($this->webDir . '/media/cache', 0777, true);
        $plugin = $this->createPlugin('media/cache');

        $markup = $plugin->imagineFilter('sample.png', 'default');
        $html = $plugin->imagineFunction('sample.png', 'default');

        $expectedUrl = '/media/cache/sample-default.png';
        $this->assertSame($expectedUrl, (string)$markup);
        $this->assertStringContainsString('src="' . $expectedUrl . '"', $html);
        $this->assertFileExists($this->webDir . $expectedUrl);
    }

    public function testImagineFunctionUsesWebRelativeCacheUrl(): void
    {
        $plugin = $this->createPlugin('cache/imagine');

        $html = $plugin->imagineFunction('sample.png', 'default', ['id' => 'img']);

        $this->assertStringContainsString('src="/cache/imagine/sample-default.png"', $html);
        $this->assertFileExists($this->webDir . '/cache/imagine/sample-default.png');
    }

    /**
     * @dataProvider invalidCachePathProvider
     */
    public function testInvalidCachePathIsRejected(string $cachePath, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->createPlugin($cachePath);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidCachePathProvider(): array
    {
        return [
            'absolute-unix' => ['/var/cache/imagine', 'web-relative path'],
            'absolute-windows' => ['C:\\cache\\imagine', 'web-relative path'],
            'empty' => ['', 'web-relative path'],
            'slash-only' => ['/', 'web-relative path'],
            'leading-traversal' => ['../cache', 'path traversal segments'],
            'traversal-escape' => ['cache/../../other', 'path traversal segments'],
        ];
    }

    private function createPlugin(string $cachePath): ImaginePlugin
    {
        $alias = new Alias([
            '@media' => $this->mediaDir,
            '@web' => $this->webDir,
        ]);
        $config = new Config([
            'urls' => ['web' => '/', 'media' => '/media'],
            'plugins' => [
                'imagine' => [
                    'cachePath' => $cachePath,
                    'collections' => [
                        'default' => [
                            'filters' => [
                                'thumbnail' => [
                                    'size' => [20, 15],
                                    'mode' => ImageInterface::THUMBNAIL_OUTBOUND,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        return new ImaginePlugin($alias, $config);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach (array_diff($items, ['.', '..']) as $item) {
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
