<?php

declare(strict_types=1);

namespace herbie\sysplugins\textile;

use herbie\Config;
use herbie\PluginName;
use herbie\sysplugins\ParserPlugin;
use Netcarver\Textile\Parser;
use Psr\Log\LoggerInterface;

#[PluginName('textile')]
final class TextilePlugin extends ParserPlugin
{
    private bool $parserClassExists;

    public function __construct(Config $config, LoggerInterface $logger)
    {
        parent::__construct($config, $logger, 'plugins.textile');
        $this->parserClassExists = class_exists('Netcarver\Textile\Parser');
        if (!$this->parserClassExists) {
            $logger->error('Please install "netcarver/textile" via composer');
        }
    }

    protected function getFormats(): array
    {
        return ['textile'];
    }

    protected function getTwigName(): string
    {
        return 'textile';
    }

    public function parse(string $value): string
    {
        if (!$this->parserClassExists) {
            return $value;
        }
        return $this->parseSafely($value, static fn(string $text): string => (new Parser())->parse($text));
    }
}
