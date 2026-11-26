<?php

declare(strict_types=1);

namespace herbie\plugins\textile;

use herbie\Config;
use herbie\Plugin;
use herbie\plugins\AbstractParserPlugin;
use Netcarver\Textile\Parser;
use Psr\Log\LoggerInterface;

#[Plugin('textile', requires: ['netcarver/textile' => '^4.0'])]
final class TextilePlugin extends AbstractParserPlugin
{
    public function __construct(Config $config, LoggerInterface $logger)
    {
        parent::__construct($config, $logger, 'plugins.textile');
    }

    #[\Override]
    protected function getFormats(): array
    {
        return ['textile'];
    }

    #[\Override]
    protected function getTwigName(): string
    {
        return 'textile';
    }

    #[\Override]
    public function parse(string $value): string
    {
        return $this->parseSafely($value, static fn(string $text): string => (new Parser())->parse($text));
    }
}
