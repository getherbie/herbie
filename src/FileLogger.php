<?php

declare(strict_types=1);

namespace herbie;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class FileLogger implements LoggerInterface
{
    private const TAB = "\t";
    private const LEVELS = [
        LogLevel::DEBUG => 0,
        LogLevel::INFO => 1,
        LogLevel::NOTICE => 2,
        LogLevel::WARNING => 3,
        LogLevel::ERROR => 4,
        LogLevel::CRITICAL => 5,
        LogLevel::ALERT => 6,
        LogLevel::EMERGENCY => 7,
    ];
    private string $file;
    private string $channel;
    private int $level;

    public function __construct(string $file, string $channel, string $level = LogLevel::DEBUG)
    {
        $this->file = $file;
        $this->channel = $channel;
        $this->setLevel($level);
    }

    public function setLevel(string $level): void
    {
        if (!array_key_exists($level, self::LEVELS)) {
            $message = "Log level $level is not a valid log level.";
            $message .= " Must be one of (" . implode(', ', array_keys(self::LEVELS)) . ')';
            throw new \DomainException($message);
        }

        $this->level = self::LEVELS[$level];
    }

    public function debug($message, array $context = [])
    {
        if ($this->logAtThisLevel(LogLevel::DEBUG)) {
            $this->log(LogLevel::DEBUG, $message, $context);
        }
    }

    public function info($message, array $context = [])
    {
        if ($this->logAtThisLevel(LogLevel::INFO)) {
            $this->log(LogLevel::INFO, $message, $context);
        }
    }

    public function notice($message, array $context = [])
    {
        if ($this->logAtThisLevel(LogLevel::NOTICE)) {
            $this->log(LogLevel::NOTICE, $message, $context);
        }
    }

    public function warning($message, array $context = [])
    {
        if ($this->logAtThisLevel(LogLevel::WARNING)) {
            $this->log(LogLevel::WARNING, $message, $context);
        }
    }

    public function error($message, array $context = [])
    {
        if ($this->logAtThisLevel(LogLevel::ERROR)) {
            $this->log(LogLevel::ERROR, $message, $context);
        }
    }

    public function critical($message, array $context = [])
    {
        if ($this->logAtThisLevel(LogLevel::CRITICAL)) {
            $this->log(LogLevel::CRITICAL, $message, $context);
        }
    }

    public function alert($message, array $context = [])
    {
        if ($this->logAtThisLevel(LogLevel::ALERT)) {
            $this->log(LogLevel::ALERT, $message, $context);
        }
    }

    public function emergency($message, array $context = [])
    {
        if ($this->logAtThisLevel(LogLevel::EMERGENCY)) {
            $this->log(LogLevel::EMERGENCY, $message, $context);
        }
    }

    public function log($level, $message, array $context = [])
    {
        // Build log line
        $pid = (int)getmypid();
        [$exception, $data] = $this->handleException($context);
        $data = $data ? json_encode($data, \JSON_UNESCAPED_SLASHES) : '{}';
        $data = $data ?: '{}'; // Fail-safe in case json_encode fails.
        $line = $this->formatLine($level, $pid, $message, $data, $exception);

        // Log to file
        try {
            $fh = fopen($this->file, 'a');
            if (!$fh) {
                throw new \RuntimeException('File open failed.');
            }
            fwrite($fh, $line);
            fclose($fh);
        } catch (\Throwable $e) {
            $message = "Could not open log file {$this->file} for writing to channel {$this->channel}!";
            throw new \RuntimeException($message, 0, $e);
        }
    }

    private function logAtThisLevel(string $level): bool
    {
        return self::LEVELS[$level] >= $this->level;
    }

    private function handleException(array $data = []): array
    {
        if (isset($data['exception']) && $data['exception'] instanceof \Throwable) {
            $exception = $data['exception'];
            $exceptionData = $this->buildExceptionData($exception);
            unset($data['exception']);
        } else {
            $exceptionData = '{}';
        }

        return [$exceptionData, $data];
    }

    private function buildExceptionData(\Throwable $e): string
    {
        $exceptionData = json_encode(
            [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace()
            ],
            \JSON_UNESCAPED_SLASHES
        );

        // Fail-safe in case json_encode failed
        return $exceptionData ?: '{"message":"' . $e->getMessage() . '"}';
    }

    private function formatLine(string $level, int $pid, string $message, string $data, string $exception_data): string
    {
        return
            $this->getTime() . self::TAB .
            "[$level]" . self::TAB .
            "[{$this->channel}]" . self::TAB .
            "[pid:$pid]" . self::TAB .
            str_replace(\PHP_EOL, '   ', trim($message)) . self::TAB .
            str_replace(\PHP_EOL, '   ', $data) . self::TAB .
            str_replace(\PHP_EOL, '   ', $exception_data) . \PHP_EOL;
    }

    private function getTime(): string
    {
        return (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s.u');
    }
}
