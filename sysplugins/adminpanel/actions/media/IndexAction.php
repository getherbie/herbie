<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-02-10
 * Time: 11:26
 */

namespace herbie\sysplugins\adminpanel\actions\media;

use herbie\Alias;
use herbie\FileInfo;
use herbie\sysplugins\adminpanel\classes\DirectoryDotFilter;
use herbie\sysplugins\adminpanel\classes\DirectoryIterator;
use Psr\Http\Message\ServerRequestInterface;

class IndexAction
{
    /**
     * @var ServerRequestInterface
     */
    private $request;
    /**
     * @var Alias
     */
    private $alias;

    public function __construct(ServerRequestInterface $request, Alias $alias)
    {
        $this->request = $request;
        $this->alias = $alias;
    }

    public function __invoke()
    {
        $params = $this->request->getQueryParams();
        $dir = $params['dir'] ?? '';
        $dir = str_replace(['../', '..', './', '.'], '', trim($dir, '/'));
        $path = $this->alias->get('@media/' . $dir);
        $root = $this->alias->get('@media');

        /*
        $entries = [];
        foreach (array_diff(scandir($path), ['..', '.']) as $i => $entry) {
            $filepath = $path . $entry;
            if (!file_exists($filepath)) {
                throw \Exception('File not exists');
            }
            $type = is_dir($filepath) ? 'dir' : 'file';
            $info = pathinfo($filepath);
            $entries[] = [
                'type' => $type,
                'path' => '',
                'name' => $entry,
                'size' => 0,
                'ext' => ($type == 'file') ? $info['extension'] : ''
            ];
        }
        */

        return [
            'currentDir' => $dir,
            'parentDir' => str_replace('.', '', dirname($dir)),
            'entries' => $this->getFiles($path, $root),
            #'root' => $root
        ];
    }

    private function getFiles(string $path, string $root): array
    {
        $iterator = [];
        if (is_readable($path)) {
            $directoryIterator = new DirectoryIterator($path, $root);
            $iterator = new DirectoryDotFilter($directoryIterator);
        }

        $files = [];
        foreach ($iterator as $it) {
            /** @var $it FileInfo */
            $files[] = [
                'type' => $it->getType(),
                'path' => $it->getRelativePathname(),
                'name' => $it->getFilename(),
                'size' => $it->getSize(),
                'ext' => $it->getExtension(),
            ];
        }

        return $files;
    }
}
