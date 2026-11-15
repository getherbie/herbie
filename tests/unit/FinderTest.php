<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Finder;

final class FinderTest extends Unit
{
    private string $fixturePath;

    public function testPageFilesFindsConfiguredExtensions(): void
    {
        $finder = new Finder(['path' => $this->fixturePath, 'extensions' => ['md', 'txt']]);

        $names = $this->getBasenames($finder->pageFiles());

        $this->assertContains('page.md', $names);
        $this->assertContains('other.txt', $names);
        $this->assertNotContains('script.php', $names);
    }

    public function testPageFilesIgnoresUnderscoreFiles(): void
    {
        $finder = new Finder(['path' => $this->fixturePath, 'extensions' => ['md']]);

        $names = $this->getBasenames($finder->pageFiles());

        $this->assertNotContains('_hidden.md', $names);
    }

    public function testPageFilesIgnoresUnderscoreDirectories(): void
    {
        $finder = new Finder(['path' => $this->fixturePath, 'extensions' => ['md']]);

        $names = $this->getBasenames($finder->pageFiles());

        $this->assertNotContains('inFolder.md', $names);
    }

    public function testPageFilesFindsNestedFiles(): void
    {
        $finder = new Finder(['path' => $this->fixturePath, 'extensions' => ['md']]);

        $names = $this->getBasenames($finder->pageFiles());

        $this->assertContains('nested.md', $names);
    }

    public function testPageFilesIsSortedByRelativePath(): void
    {
        $finder = new Finder(['path' => $this->fixturePath, 'extensions' => ['md']]);

        $paths = [];
        foreach ($finder->pageFiles() as $file) {
            $paths[] = $file->getRelativePathname();
        }

        // sortByName() sorts by the relative path, so the nested file comes last
        $this->assertSame(['page.md', 'sub/nested.md'], $paths);
    }

    /**
     * Without configured extensions the name filter is built from an empty list,
     * so it does not filter at all and every file is returned.
     */
    public function testPageFilesWithoutExtensionsFindsEveryFile(): void
    {
        $finder = new Finder(['path' => $this->fixturePath]);

        $names = $this->getBasenames($finder->pageFiles());

        $this->assertContains('page.md', $names);
        $this->assertContains('script.php', $names);
        $this->assertContains('other.txt', $names);
    }

    /**
     * @return array<string>
     */
    private function getBasenames(iterable $files): array
    {
        $names = [];
        foreach ($files as $file) {
            $names[] = $file->getBasename();
        }
        return $names;
    }

    protected function _before(): void
    {
        $this->fixturePath = dirname(__DIR__) . '/_data/finder';
    }
}
