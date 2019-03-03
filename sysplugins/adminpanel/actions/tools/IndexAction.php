<?php

namespace herbie\sysplugins\adminpanel\actions\tools;

use herbie\Alias;
use function herbie\count_files;
use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;

class IndexAction
{
    /**
     * @var Alias
     */
    private $alias;

    /**
     * @var PayloadFactory
     */
    private $payloadFactory;

    /**
     * IndexAction constructor.
     * @param Alias $alias
     * @param PayloadFactory $payloadFactory
     */
    public function __construct(Alias $alias, PayloadFactory $payloadFactory)
    {
        $this->alias = $alias;
        $this->payloadFactory = $payloadFactory;
    }

    /**
     * @return Payload
     */
    public function __invoke(): Payload
    {
        $payload = $this->payloadFactory->newInstance();
        $data = $this->getData();
        return $payload
            ->setStatus(Payload::FOUND)
            ->setOutput($data);
    }

    /**
     * @return array
     */
    private function getData(): array
    {
        return [
            'files' => $this->getEmptieableFolders(),
            'configs' => $this->getConfigFiles()
        ];
    }

    /**
     * @return array
     */
    private function getEmptieableFolders(): array
    {
        return [
            [
                'label' => 'Data cache',
                'count' => $this->countFiles('@site/runtime/cache/data'),
                'alias' => '@site/runtime/cache/data',
                'confirm' => 'Empty data cache?'
            ],
            [
                'label' => 'Page cache',
                'count' => $this->countFiles('@site/runtime/cache/page'),
                'alias' => '@site/runtime/cache/page',
                'confirm' => 'Empty page cache?'
            ],
            [
                'label' => 'Twig cache',
                'count' => $this->countFiles('@site/runtime/cache/twig'),
                'alias' => '@site/runtime/cache/twig',
                'confirm' => 'Empty twig cache?'
            ],
            [
                'label' => 'Web assets',
                'count' => $this->countFiles('@web/assets'),
                'alias' => '@web/assets',
                'confirm' => 'Empty web assets?'
            ],
            [
                'label' => 'Log',
                'count' => $this->countFiles('@site/runtime/log'),
                'alias' => '@site/runtime/log',
                'confirm' => 'Empty log dir?'
            ],
        ];
    }

    /**
     * @param $alias
     * @return int
     */
    private function countFiles($alias): int
    {
        $path = $this->alias->get($alias);
        return count_files($path);
    }

    /**
     * @return array
     */
    private function getConfigFiles(): array
    {
        $alias = '@site';
        $path = $this->alias->get($alias);
        $directory = new \RecursiveDirectoryIterator($path);
        $iterator = new \RecursiveIteratorIterator($directory);
        $regex = new \RegexIterator($iterator, '/^.+\.yml$/i', \RecursiveRegexIterator::GET_MATCH);

        $files = [];
        foreach ($regex as $file) {
            $info = pathinfo($file[0]);
            $files[] = [
                'label' => $info['basename'],
                'alias' => str_replace($path, $alias, $info['dirname']) . '/' . $info['basename'],
                'confirm' => 'Format configuration?'
            ];
        }
        return $files;
    }
}
