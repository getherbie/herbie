<?php

declare(strict_types=1);

namespace tests\integration\SysPlugin;

use herbie\Application;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

final class DummySysPluginTest extends \Codeception\Test\Unit
{
    protected Application $app;

    protected function initApplication(string $sitePath, string $vendorPath, string $logPath): Application
    {
        if (is_file($logPath)) {
            unlink($logPath); // delete log file if exists
        }

        $logger = new Logger('herbie');
        $logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));

        return new Application($sitePath, $vendorPath, $logger);
    }

    public function testTextileFilter(): void
    {
        $logFile = dirname(__DIR__) . '/Fixtures/site/runtime/log/logger.log';

        $this->initApplication(
            dirname(__DIR__) . '/Fixtures/site',
            dirname(__DIR__, 3) . '/vendor',
            $logFile
        )->run();

        // These tests are bad and really(!) should be made like here:
        // https://stackoverflow.com/a/70355297/6161354

        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString('Event onSystemPluginsAttached was triggered', $logContent);
        $this->assertStringContainsString('Event onComposerPluginsAttached was triggered', $logContent);
        $this->assertStringContainsString('Event onLocalPluginsAttached was triggered', $logContent);
        $this->assertStringContainsString('Event onPluginsAttached was triggered', $logContent);
        $this->assertStringContainsString('Event onTwigInitialized was triggered', $logContent);
        $this->assertStringContainsString('Event onContentRendered was triggered', $logContent);
        // strangly, this event is not triggered
        // $this->assertStringContainsString('Event onLayoutRendered was triggered', $logContent);
        $this->assertStringContainsString('Event onResponseGenerated was triggered', $logContent);
        $this->assertStringContainsString('Event onResponseEmitted was triggered', $logContent);
    }
}
