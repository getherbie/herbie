<?php

declare(strict_types=1);

namespace herbie\sysplugins\dummy;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class DummyCommand extends Command
{
    protected static $defaultName = 'dummy';
    protected static $defaultDescription = 'Creates a new user.';

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows you to create a user...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'User Creator',
            '============',
            '',
        ]);

        $output->writeln('Whoa!');
        $output->write('You are about to ');
        $output->write('create a user.');

        return Command::SUCCESS;
    }
}
