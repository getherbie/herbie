<?php

declare(strict_types=1);

namespace herbie;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ClearCacheCommand extends Command
{
    private Config $config;
    protected static $defaultName = 'clear-cache';
    protected static $defaultDescription = 'Clears herbies internal cache';

    public function __construct(Config $config)
    {
        $this->config = $config;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('The clear-cache deletes cached files from herbies cache directory.')
            ->addArgument('type', InputArgument::OPTIONAL, 'Type of cache to delete', 'all')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument('type');
        $pathsToClear = $this->getPathsToClear($type);
        $filesToIgnores = ['.gitignore'];

        foreach ($pathsToClear as $path) {
            $this->clearPath($path, $filesToIgnores);
        }

        $output->writeln('You cleared the cache!');
        return Command::SUCCESS;
    }

    private function getPathsToClear(string $type): array
    {
        $runtimePath = $this->config->getAsString('paths.site');

        $cachePaths = [
            'data' => $runtimePath . '/runtime/cache/data',
            'page' => $runtimePath . '/runtime/cache/page',
            'twig' => $runtimePath . '/runtime/cache/twig',
        ];

        if ($type === 'all') {
            return $cachePaths;
        }

        return isset($cachePaths[$type]) ? [$cachePaths[$type]] : [];
    }

    private function clearPath(string $path, array $filesToIgnore): void
    {
        $it = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                if (!in_array($file->getFilename(), $filesToIgnore)) {
                    unlink($file->getRealPath());
                }
            }
        }
        //rmdir($path);
    }
}
