<?php

declare(strict_types=1);

namespace herbie\tests\integration\Docs;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Source;
use TwigCsFixer\Environment\StubbedEnvironment;
use TwigCsFixer\Report\Report;
use TwigCsFixer\Rules\Literal\SingleQuoteRule;
use TwigCsFixer\Token\Tokenizer;
use UnitTester;

final class TwigExamplesTest extends Unit
{
    protected UnitTester $tester;

    public function testTwigExamplesFromTheDocumentationRender(): void
    {
        $renderer = $this->twig();
        $examples = $this->examples();
        $this->assertNotEmpty($examples, 'No Twig examples found in the documentation.');

        $failures = [];
        foreach ($examples as [$file, $line, $code]) {
            try {
                $renderer->renderString($code, $this->context());
            } catch (Throwable $e) {
                $failures[] = sprintf('%s:%d: %s: %s', $file, $line, $e::class, $e->getMessage());
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function testTwigExamplesUseSingleQuotes(): void
    {
        $tokenizer = new Tokenizer(new StubbedEnvironment());
        $rule = new SingleQuoteRule();
        $failures = [];

        foreach ($this->examples() as [$file, $line, $code]) {
            $report = new Report([new SplFileInfo($file)]);
            $rule->lintFile($tokenizer->tokenize(new Source($code, $file)), $report);

            foreach ($report->getViolations() as $violation) {
                $failures[] = sprintf(
                    '%s:%d: %s',
                    $file,
                    $line + (int)$violation->getLine(),
                    $violation->getMessage(),
                );
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    private function twig(): TwigRenderer
    {
        $renderer = $this->tester->initTwigRenderer(
            dirname(__DIR__, 3),
            dirname(__DIR__, 1) . '/Fixtures/site',
        );

        $loader = new FilesystemLoader();
        $loader->addPath(dirname(__DIR__, 1) . '/Fixtures/site/snippets', 'snippet');
        $chain = $renderer->getTwigEnvironment()->getLoader();
        if (!$chain instanceof ChainLoader) {
            throw new RuntimeException('Unexpected Twig loader in the test site.');
        }
        $chain->addLoader($loader);

        return $renderer;
    }

    /**
     * @return array<string, mixed>
     */
    private function context(): array
    {
        return [
            'page' => [
                'title' => 'Example page',
                'class' => 'example',
                'layout' => 'default',
            ],
            'content' => [
                'default' => '<p>Default</p>',
                'left' => '<p>Left</p>',
                'right' => '<p>Right</p>',
                'sidebar' => '<p>Sidebar</p>',
            ],
        ];
    }

    /**
     * @return array<array{string, int, string}>
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
            $buffer = [];
            $start = 0;
            foreach ($lines as $index => $line) {
                if ($fence === null) {
                    if (preg_match('/^(~{3,}|`{3,})\s*twig\b(.*)$/', $line, $matches) === 1
                        && !str_contains($matches[2], 'no-test')
                    ) {
                        $fence = $matches[1];
                        $buffer = [];
                        $start = $index + 1;
                    }
                    continue;
                }

                if (str_starts_with(trim($line), $fence)) {
                    $examples[] = [
                        substr($file->getPathname(), strlen(dirname(__DIR__, 3)) + 1),
                        $start,
                        implode("\n", $buffer),
                    ];
                    $fence = null;
                    continue;
                }

                $buffer[] = $line;
            }
        }

        return $examples;
    }
}
