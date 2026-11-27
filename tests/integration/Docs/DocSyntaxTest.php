<?php

declare(strict_types=1);

namespace herbie\tests\integration\Docs;

use Codeception\Test\Unit;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Throwable;

final class DocSyntaxTest extends Unit
{
    private const LANGUAGES = ['bash', 'json', 'php', 'yaml'];

    public function testCodeFencesInTheDocumentationAreValid(): void
    {
        $examples = $this->examples();
        $this->assertNotEmpty($examples, 'No code examples found in the documentation.');

        $failures = [];
        foreach ($examples as [$file, $line, $language, $code]) {
            try {
                $this->check($language, $code);
            } catch (Throwable $e) {
                $failures[] = sprintf('%s:%d [%s]: %s', $file, $line, $language, $e->getMessage());
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    private function check(string $language, string $code): void
    {
        match ($language) {
            'bash' => $this->checkBash($code),
            'json' => json_decode($code, true, 512, JSON_THROW_ON_ERROR),
            'php' => $this->checkPhp($code),
            'yaml' => $this->checkYaml($code),
        };
    }

    private function checkBash(string $code): void
    {
        $error = $this->lint('bash -n', $code);
        if ($error !== null) {
            throw new RuntimeException($error);
        }
    }

    private function checkPhp(string $code): void
    {
        $command = escapeshellarg(PHP_BINARY) . ' -l';
        $sources = [str_starts_with(ltrim($code), '<?php') ? $code : "<?php\n" . $code];
        if (str_contains($code, '=>') && !str_contains($code, 'return [')
            && !str_starts_with(ltrim($code), '<?php')
        ) {
            $sources[] = "<?php\nreturn [\n" . $code . "\n];\n";
        }

        $error = null;
        foreach ($sources as $source) {
            $error = $this->lint($command, $source);
            if ($error === null) {
                return;
            }
        }

        if ($error !== null) {
            $error = preg_replace('/\s+in Standard input code on line \d+/', '', $error) ?? $error;
            throw new RuntimeException(explode("\n", trim($error))[0]);
        }
    }

    private function lint(string $command, string $code): ?string
    {
        $process = proc_open(
            $command,
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
        );
        if (!is_resource($process)) {
            return sprintf('Unable to start %s.', $command);
        }

        fwrite($pipes[0], $code);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode === 0) {
            return null;
        }

        return trim((string)$stderr) ?: trim((string)$stdout) ?: sprintf(
            '%s exited with code %d',
            $command,
            $exitCode,
        );
    }

    private function checkYaml(string $code): void
    {
        $documents = preg_split('/^[ \t]*---(?:[^\n]*---)?[ \t]*$/m', $code);
        if ($documents === false) {
            throw new RuntimeException('Unable to split the YAML example into documents.');
        }

        foreach ($documents as $document) {
            if (trim($document) === '') {
                continue;
            }
            Yaml::parse($document);
        }
    }

    /**
     * @return array<int, array{string, int, string, string}>
     */
    private function examples(): array
    {
        $root = dirname(__DIR__, 3) . '/website/site/pages';
        $examples = [];

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            if (!$file->isFile() || $file->getExtension() !== 'md') {
                continue;
            }
            $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES);
            if ($lines === false) {
                continue;
            }

            $fence = null;
            $language = '';
            $buffer = [];
            $start = 0;
            foreach ($lines as $index => $line) {
                if ($fence === null) {
                    if (preg_match('/^(~{3,}|`{3,})\s*([\w-]*)(.*)$/', $line, $matches) === 1) {
                        $fence = $matches[1];
                        $language = strtolower($matches[2]);
                        if (in_array($language, self::LANGUAGES, true) && !str_contains($matches[3], 'no-test')) {
                            $buffer = [];
                            $start = $index + 1;
                        } else {
                            $fence = null;
                        }
                    }
                    continue;
                }

                if (str_starts_with(trim($line), $fence)) {
                    if (trim(implode("\n", $buffer)) !== '') {
                        $examples[] = [
                            substr($file->getPathname(), strlen(dirname(__DIR__, 3)) + 1),
                            $start,
                            $language,
                            implode("\n", $buffer),
                        ];
                    }
                    $fence = null;
                    continue;
                }

                $buffer[] = $line;
            }
        }

        return $examples;
    }
}
