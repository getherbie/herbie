<?php

declare(strict_types=1);

namespace tests\_data\src;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CustomCommand extends Command
{
    protected static $defaultName = 'custom';
    protected static $defaultDescription = 'Does nothing.';

    protected function configure(): void
    {
        $this->setHelp('This command does nothing.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(__METHOD__);
        return Command::SUCCESS;
    }
}
