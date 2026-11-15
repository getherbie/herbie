<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\JsonDataRepository;
use herbie\SystemException;
use herbie\YamlDataRepository;

final class DataRepositoryTest extends Unit
{
    private string $fixturePath;

    public function testLoadYamlFile(): void
    {
        $repository = new YamlDataRepository($this->fixturePath);

        $data = $repository->load('media');

        $this->assertSame(['image' => 'photo.jpg', 'title' => 'A photo'], $data);
    }

    public function testLoadReturnsEmptyArrayForUnknownName(): void
    {
        $repository = new YamlDataRepository($this->fixturePath);

        $this->assertSame([], $repository->load('not-existing'));
    }

    public function testLoadIsCaseInsensitive(): void
    {
        $repository = new YamlDataRepository($this->fixturePath);

        $this->assertNotSame([], $repository->load('MEDIA'));
    }

    public function testLoadYamlWithYamlExtension(): void
    {
        $repository = new YamlDataRepository($this->fixturePath);

        $data = $repository->load('menu');

        $this->assertSame(['name' => 'Home', 'route' => ''], $data);
    }

    public function testLoadAll(): void
    {
        $repository = new YamlDataRepository($this->fixturePath);

        $data = $repository->loadAll();

        $this->assertArrayHasKey('media', $data);
        $this->assertArrayHasKey('menu', $data);
        $this->assertSame(['name' => 'Home', 'route' => ''], $data['menu']);
    }

    public function testMissingDirectoryThrows(): void
    {
        $this->expectException(SystemException::class);
        new YamlDataRepository($this->fixturePath . '/not-existing');
    }

    public function testLoadJsonFile(): void
    {
        $repository = new JsonDataRepository($this->fixturePath);

        $data = $repository->load('json');

        $this->assertSame(['image' => 'photo.jpg', 'title' => 'A photo'], $data);
    }

    public function testJsonRepositoryIgnoresYamlFiles(): void
    {
        $repository = new JsonDataRepository($this->fixturePath);

        $this->assertSame([], $repository->load('menu'));
    }

    /**
     * The scanned files were kept in a static variable before, so the first
     * repository in a process defined the result for all others, regardless
     * of the path and the extension.
     */
    public function testRepositoriesDoNotShareTheScannedFiles(): void
    {
        $yaml = new YamlDataRepository($this->fixturePath);
        $json = new JsonDataRepository($this->fixturePath);

        $this->assertArrayHasKey('media', $yaml->loadAll());
        $this->assertArrayNotHasKey('media', $json->loadAll());
        $this->assertArrayHasKey('json', $json->loadAll());
    }

    public function testJsonRepositoryDoesNotCheckTheDirectory(): void
    {
        // the constructor of the parent class is not called, so a missing
        // directory is not detected while constructing
        $repository = new JsonDataRepository($this->fixturePath . '/not-existing');

        $this->assertInstanceOf(JsonDataRepository::class, $repository);
    }

    protected function _before(): void
    {
        $this->fixturePath = dirname(__DIR__) . '/_data/data';
    }
}
