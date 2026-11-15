<?php

declare(strict_types=1);

namespace herbie\sysplugins\rst;

use Doctrine\RST\Parser;
use herbie\Config;
use herbie\PluginName;
use herbie\sysplugins\ParserPlugin;
use Psr\Log\LoggerInterface;

#[PluginName('rst')]
final class RstPlugin extends ParserPlugin
{
    private bool $parserClassExists;

    public function __construct(Config $config, LoggerInterface $logger)
    {
        parent::__construct($config, $logger, 'plugins.rst');
        $this->parserClassExists = class_exists('Doctrine\RST\Parser');
        if (!$this->parserClassExists) {
            $logger->error('Please install "doctrine/rst-parser" via composer');
        }
    }

    protected function getFormats(): array
    {
        return ['rst'];
    }

    protected function getTwigName(): string
    {
        return 'rst';
    }

    public function parse(string $value): string
    {
        if (!$this->parserClassExists) {
            return $value;
        }
        return $this->parseSafely(
            $value,
            static fn(string $text): string => (new Parser())->parse($text)->render(),
        );
    }
}
