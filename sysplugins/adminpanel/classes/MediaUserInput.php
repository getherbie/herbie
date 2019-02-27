<?php

namespace herbie\sysplugins\adminpanel\classes;

use Ausi\SlugGenerator\SlugGenerator;
use herbie\Alias;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class MediaUserInput
{
    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var Alias
     */
    private $alias;

    /**
     * @var SlugGenerator
     */
    private $slugGenerator;

    public function __construct(Alias $alias, ServerRequestInterface $request, SlugGenerator $slugGenerator)
    {
        $this->request = $request;
        $this->alias = $alias;
        $this->slugGenerator = $slugGenerator;
    }

    /**
     * @return string
     */
    public function getFolderName(): string
    {
        $input = json_decode($this->request->getBody(), true);
        $folderName = strtolower(trim($input['folderName']));
        $folderName = preg_replace('/[^a-z0-9_-]+/', '-', $folderName);
        $folderName = trim($folderName);
        return $folderName;
    }

    public function getCurrentDir(): string
    {
        $contentType = $this->request->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode($this->request->getBody(), true);
        } else {
            $input = $this->request->getParsedBody();
        }

        $mediaPath = $this->alias->get('@media/');
        $currentDir = trim($input['currentDir']);

        if ($this->isValidSubPath($mediaPath, $currentDir)) {
            return $currentDir;
        }
        throw new \Exception('Invalid path' . $currentDir);
    }

    public function getUploadFile()
    {
        $files = $this->request->getUploadedFiles();
        foreach ($files as $file) {
            /** @var UploadedFileInterface $file */
            $error = $file->getError();
            if ($error > 0) {
                $message = $this->codeToMessage($error);
                throw new \Exception($message, $error);
            }

            return $file;
        }
        throw new \Exception('No file uploaded');
    }

    public function getDeleteFile()
    {
        $input = json_decode($this->request->getBody(), true);
        $file = $input['file'] ?? '';
        return $file;
    }

    private function isValidSubPath(string $absolutePath, string $relativeSubPath): string
    {
        $realRootPath = realpath($absolutePath);
        $realSubPath = realpath($realRootPath . '/' . $relativeSubPath);

        if (strpos($realSubPath, $realRootPath) !== 0) {
            return false;
        }
        return true;
    }

    /**
     * @param string $clientFileName
     * @return string
     */
    public function sanitizeClientFilename(string $clientFileName): string
    {
        $info = pathinfo($clientFileName);
        $extension = $info['extension'];
        $basename = $info['filename'];
        $filename = sprintf('%s.%s', $this->slugGenerator->generate($basename), $extension);
        return $filename;
    }

    /**
     * @param $code
     * @return string
     * @see http://php.net/manual/de/features.file-upload.errors.php
     */
    private function codeToMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;
            default:
                $message = "Unknown upload error";
                break;
        }
        return $message;
    }
}
