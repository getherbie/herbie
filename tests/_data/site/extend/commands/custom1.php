<?php

declare(strict_types=1);

namespace herbie\tests\_data\site\extend\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CustomCommand extends Command
{
    protected static $defaultName = 'custom1';
    protected static $defaultDescription = 'Does nothing.';

    protected function configure(): void
    {
        $this
            ->setHelp('This command does nothing.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Whoa!');
        return Command::SUCCESS;
    }
}

return CustomCommand::class;
