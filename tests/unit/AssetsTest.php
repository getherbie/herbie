<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Alias;
use herbie\Assets;
use InvalidArgumentException;

final class AssetsTest extends Unit
{
    private string $fixturePath;

    public function testOutputCssIsEmptyByDefault(): void
    {
        $assets = $this->createAssets();

        $this->assertSame('', $assets->outputCss());
        $this->assertSame('', $assets->outputJs());
    }

    public function testAddCssCreatesLinkTag(): void
    {
        $assets = $this->createAssets();
        $assets->addCss('@src/css/styles.css');

        $html = $assets->outputCss();

        $this->assertStringContainsString('<link', $html);
        $this->assertStringContainsString('rel="stylesheet"', $html);
        $this->assertStringContainsString('css/styles.css', $html);
    }

    public function testAddJsCreatesScriptTag(): void
    {
        $assets = $this->createAssets();
        $assets->addJs('@src/js/app.js');

        $html = $assets->outputJs();

        $this->assertStringContainsString('<script', $html);
        $this->assertStringContainsString('js/app.js', $html);
    }

    public function testAssetIsPublishedToTheWebDirectory(): void
    {
        $assets = $this->createAssets();
        $assets->addCss('@src/css/styles.css');
        $assets->outputCss();

        $this->assertFileExists($this->fixturePath . '/web/assets/css/styles.css');
    }

    /**
     * A file is published only once per process, the static cache survives
     * across instances.
     */
    public function testAssetIsPublishedOnlyOnce(): void
    {
        $marker = $this->fixturePath . '/web/assets/css/first.css';

        $first = $this->createAssets();
        $first->addCss('@src/css/first.css');
        $first->outputCss();
        $firstModified = filemtime($marker);

        $second = $this->createAssets();
        $second->addCss('@src/css/first.css');
        $second->outputCss();

        $this->assertSame($firstModified, filemtime($marker));
    }

    public function testOutputCssOnlyReturnsRequestedGroup(): void
    {
        $assets = $this->createAssets();
        $assets->addCss('@src/css/print.css', [], 'print');
        $assets->addCss('@src/css/first.css', [], 'default');

        $this->assertStringContainsString('first.css', $assets->outputCss('default'));
        $this->assertStringNotContainsString('print.css', $assets->outputCss('default'));
        $this->assertStringContainsString('print.css', $assets->outputCss('print'));
    }

    public function testAttributesAreRendered(): void
    {
        $assets = $this->createAssets();
        $assets->addCss('@src/css/first.css', ['media' => 'print']);

        $this->assertStringContainsString('media="print"', $assets->outputCss());
    }

    public function testTimestampIsAddedWhenRequested(): void
    {
        $assets = $this->createAssets();
        $assets->addCss('@src/css/first.css');

        $this->assertStringNotContainsString('?t=', $assets->outputCss(null, false));
        $this->assertStringContainsString('?t=', $assets->outputCss(null, true));
    }

    public function testRawCssIsPrintedInline(): void
    {
        $assets = $this->createAssets();
        $assets->addCss('body { margin: 0; }', [], null, true);

        $html = $assets->outputCss();

        $this->assertStringContainsString('<style', $html);
        $this->assertStringContainsString('body { margin: 0; }', $html);
    }

    public function testRawAssetIsNotPublished(): void
    {
        $marker = $this->fixturePath . '/web/assets/raw-not-published.css';
        $assets = $this->createAssets();
        $assets->addCss('raw-not-published', [], null, true);
        $assets->outputCss();

        $this->assertFileDoesNotExist($marker);
    }

    public function testAbsoluteUrlIsNotPublished(): void
    {
        $assets = $this->createAssets();
        $assets->addCss('https://example.com/styles.css');

        $html = $assets->outputCss();

        $this->assertStringContainsString('https://example.com/styles.css', $html);
    }

    public function testUnknownAliasThrows(): void
    {
        $assets = $this->createAssets();
        $assets->addCss('@not-existing/styles.css');

        $this->expectException(InvalidArgumentException::class);
        $assets->outputCss();
    }

    private function createAlias(): Alias
    {
        return new Alias([
            '@src' => $this->fixturePath . '/src',
            '@web' => $this->fixturePath . '/web',
        ]);
    }

    private function createAssets(): Assets
    {
        return new Assets($this->createAlias(), '/');
    }

    protected function _before(): void
    {
        $this->fixturePath = dirname(__DIR__) . '/_data/assets';
        $this->resetStaticState();
    }

    protected function _after(): void
    {
        $this->removePublishedAssets();
        $this->resetStaticState();
    }

    /**
     * Assets caches the published files and the sort state in static properties.
     */
    private function resetStaticState(): void
    {
        $class = new \ReflectionClass(Assets::class);
        foreach (['published', 'sorted', 'counter'] as $name) {
            $property = $class->getProperty($name);
            $property->setValue(null, in_array($name, ['published'], true) ? [] : ($name === 'sorted' ? false : 0));
        }
    }

    private function removePublishedAssets(): void
    {
        $published = $this->fixturePath . '/web/assets';
        if (!is_dir($published)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($published, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($published);
    }
}
