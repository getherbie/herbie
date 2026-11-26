<?php

declare(strict_types=1);

namespace herbie\plugins\rst;

use Doctrine\RST\Parser;
use herbie\Config;
use herbie\Plugin;
use herbie\plugins\AbstractParserPlugin;
use Psr\Log\LoggerInterface;

#[Plugin('rst', requires: ['doctrine/rst-parser' => '^0.5.2'])]
final class RstPlugin extends AbstractParserPlugin
{
    public function __construct(Config $config, LoggerInterface $logger)
    {
        parent::__construct($config, $logger, 'plugins.rst');
    }

    #[\Override]
    protected function getFormats(): array
    {
        return ['rst'];
    }

    #[\Override]
    protected function getTwigName(): string
    {
        return 'rst';
    }

    #[\Override]
    public function parse(string $value): string
    {
        return $this->parseSafely(
            $value,
            static fn(string $text): string => (new Parser())->parse($text)->render(),
        );
    }
}
