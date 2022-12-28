<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins;

use Codeception\Test\Unit;
use herbie\Application;
use herbie\ApplicationPaths;

use function herbie\file_read;

final class DummySysPluginTest extends Unit
{
    protected Application $app;

    public function testTextileFilter(): void
    {
        $logFile = dirname(__DIR__) . '/Fixtures/site/runtime/log/logger.log';

        $this->initApplication(
            dirname(__DIR__, 3),
            dirname(__DIR__) . '/Fixtures/site',
            $logFile
        )->run();

        // These tests are bad and really(!) should be made like here:
        // https://stackoverflow.com/a/70355297/6161354

        $logContent = file_read($logFile);
        $this->assertStringContainsString('Event herbie\events\ContentRenderedEvent was triggered', $logContent);
        $this->assertStringContainsString('Event herbie\events\LayoutRenderedEvent was triggered', $logContent);
        $this->assertStringContainsString('Event herbie\events\PluginsInitializedEvent was triggered', $logContent);
        $this->assertStringContainsString('Event herbie\events\ResponseEmittedEvent was triggered', $logContent);
        $this->assertStringContainsString('Event herbie\events\ResponseGeneratedEvent was triggered', $logContent);
        $this->assertStringContainsString('Event herbie\events\TranslatorInitializedEvent was triggered', $logContent);
        $this->assertStringContainsString('Event herbie\events\TwigInitializedEvent was triggered', $logContent);
    }

    protected function initApplication(string $appPath, string $sitePath, string $logPath): Application
    {
        if (is_file($logPath)) {
            unlink($logPath); // delete log file if exists
        }
        return new Application(
            new ApplicationPaths(
                $appPath,
                $sitePath,
                dirname(__DIR__, 4) . '/vendor',
                dirname(__DIR__, 2) . '/_data/web'
            )
        );
    }
}
