<?php

declare(strict_types=1);

namespace herbie;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ClearFileCommand extends Command
{
    private Config $config;
    protected static $defaultName = 'clear-file';
    protected static $defaultDescription = 'Clears asset, cache and log files';

    public function __construct(Config $config)
    {
        $this->config = $config;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('The clear-file deletes asset, cache and log files from several directories.')
            ->addArgument('type', InputArgument::OPTIONAL, 'Type of files to delete', 'all')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument('type');
        $pathsToClear = $this->getPathsToClear($type);
        $filesToIgnores = ['.gitignore', '.gitkeep'];

        foreach ($pathsToClear as $key => $path) {
            $this->clearPath($path, $filesToIgnores);
            $message = sprintf('Clearing files (%s): %s', $key, $path);
            $output->writeln($message);
        }

        $output->writeln('All files cleared.');
        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function getPathsToClear(string $type): array
    {
        $runtimePath = $this->config->getAsString('paths.site');
        $webPath = $this->config->getAsString('paths.web');

        $cachePaths = [
            'site-cache-system' => $runtimePath . '/runtime/cache/system',
            'site-cache-twig' => $runtimePath . '/runtime/cache/twig',
            'site-log' => $runtimePath . '/runtime/log',
            'web-assets' => $webPath . '/assets',
            'web-cache' => $webPath . '/cache',
        ];

        if ($type === 'all') {
            return $cachePaths;
        }

        $items = [];
        foreach ($cachePaths as $key => $value) {
            if (strpos($key, $type) === 0) {
                $items[$key] = $value;
            }
        }

        return $items;
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
