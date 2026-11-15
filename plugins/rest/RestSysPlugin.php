<?php

declare(strict_types=1);

namespace herbie\sysplugins\rest;

use Doctrine\RST\Parser;
use herbie\Config;
use herbie\sysplugins\ParserSysPlugin;
use Psr\Log\LoggerInterface;

final class RestSysPlugin extends ParserSysPlugin
{
    private bool $parserClassExists;

    public function __construct(Config $config, LoggerInterface $logger)
    {
        parent::__construct($config, $logger, 'plugins.rest');
        $this->parserClassExists = class_exists('Doctrine\RST\Parser');
        if (!$this->parserClassExists) {
            $logger->error('Please install "doctrine/rst-parser" via composer');
        }
    }

    protected function getFormats(): array
    {
        return ['rest'];
    }

    protected function getTwigName(): string
    {
        return 'rest';
    }

    public function parse(string $value): string
    {
        if (!$this->parserClassExists) {
            return $value;
        }
        return $this->parseSafely(
            $value,
            static fn(string $text): string => (new Parser())->parse($text)->render()
        );
    }
}
