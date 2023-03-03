<?php

namespace herbie\sysplugins\adminpanel\controllers;

use Exception;
use Herbie;
use herbie\sysplugins\adminpanel\validators\FileNotExistsRule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Rakit\Validation\Validator;
use Symfony\Component\Filesystem\Exception\ExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

use function herbie\human2byte;

class MediaController extends Controller
{
    private Filesystem $fs;

    protected function init(): void
    {
        $this->fs = new Filesystem();
        $dir = $this->alias->get('@site/media');
        if (!$this->fs->exists($dir)) {
            throw new \Exception(sprintf('Dir "%s" not exist', $dir));
        }
        if (!is_writable($dir)) {
            throw new \Exception(sprintf('Dir "%s" not writable', $dir));
        }
    }

    public function addAction(ServerRequestInterface $request): ResponseInterface
    {
        $dir = $request->getQueryParams()['dir'] ?? '';

        $errors = [];
        $values = $request->getParsedBody();

        $validator = new Validator();
        $validator->addValidator('file_not_exists', new FileNotExistsRule($this->alias));

        $currentDir = ['@site/media'];
        if ($dir <> '') {
            $currentDir[] = $dir;
        }
        $currentDir[] = '{value}';

        $aliasedPathWithPlaceholder = $this->alias->get(join('/', $currentDir));
        $validation = $validator->make($values, [
            'name' => 'required|lowercase|alpha_dash|file_not_exists:' . $aliasedPathWithPlaceholder,
        ]);

        if ($request->getMethod() === 'POST') {
            $validation->validate();
            if ($validation->fails() || !empty($request->getHeader('X-Up-Validate'))) {
                $errors = $validation->errors()->firstOfAll();
            } else {
                $dirToCreate = str_replace('{value}', $values['name'], $this->alias->get($aliasedPathWithPlaceholder));
                try {
                    $this->fs->mkdir($dirToCreate);
                    return $this->redirect('media/index&dir=' . $dir);
                } catch (ExceptionInterface $e) {
                    $errors['name'] = $this->t('Folder "{name}" can not be created.', ['name' => $values['name']]);
                }
            }
        }

        $status = empty($errors) ? 200 : 400;

        return $this->render('media/add.twig', [
            'errors' => $errors,
            'values' => $values,
            'dir' => $dir,
        ], $status);
    }

    public function indexAction(ServerRequestInterface $request)
    {
        $dir = $request->getQueryParams()['dir'] ?? '';
        $root = $this->alias->get('@media');

        $iterator = $this->finder->mediaFiles($dir);

        return $this->render('media/index.twig', [
            'iterator' => $iterator,
            'dir' => $dir,
            'parentDir' => str_replace('.', '', dirname($dir)),
            'root' => $root
        ]);
    }

    public function deleteFileAction(ServerRequestInterface $request)
    {
        $path = $request->getQueryParams()['path'] ?? '';
        $dir = ltrim(dirname(str_replace('@media', '', $path)), '/');
        $absPath = $this->alias->get($path);

        $errors = [];

        if ($request->getMethod() === 'POST') {
            if (!is_file($absPath)) {
                $errors['name'] = $this->t('File {name} does not exist.', ['name' => $path]);
            }
            if (!@unlink($absPath)) {
                $errors['name'] = $this->t('File {name} can not be deleted.', ['name' => $path]);
            }
            if (empty($errors)) {
                return $this->redirect('media/index&dir=' . $dir);
            }
        }

        $status = empty($errors) ? 200 : 400;

        return $this->render('media/delete-file.twig', [
            'errors' => $errors,
            'path' => $path,
        ], $status);
    }

    public function deleteFolderAction(ServerRequestInterface $request)
    {
        $path = $request->getQueryParams()['path'] ?? '';
        $dir = ltrim(dirname(str_replace('@media', '', $path)), '/');
        $absPath = $this->alias->get($path);

        $files = scandir($absPath);
        $hasContent = count($files) > 2;

        $errors = [];

        if ($request->getMethod() === 'POST') {
            if (!is_dir($absPath)) {
                $errors['name'] = $this->t('Folder {name} does not exist.', ['name' => $path]);
            }
            if (!@rmdir($absPath)) {
                $errors['name'] = $this->t('Folder {name} can not be deleted.', ['name' => $path]);
            }
            if (empty($errors)) {
                return $this->redirect('media/index&dir=' . $dir);
            }
        }

        $status = empty($errors) ? 200 : 400;

        return $this->render('media/delete-folder.twig', [
            'errors' => $errors,
            'path' => $path,
            'hasContent' => $hasContent
        ], $status);
    }

    public function uploadAction(ServerRequestInterface $request)
    {
        /**
         * @var UploadedFileInterface[] $files
         */
        $dir = strtolower(trim($request->getQueryParams()['dir'] ?? ''));

        $errors = [];
        $files = $request->getUploadedFiles()['upload'] ?? [];

        if ($request->getMethod() === 'POST') {
            $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);

            if ($contentLength > 0) {
                $postMaxSize = ini_get('post_max_size');
                $postMaxSizeAsInt = human2byte($postMaxSize);
                if ($contentLength > $postMaxSizeAsInt) {
                    $message = 'The uploaded files are too large.';
                    $message .= ' According to your php.ini the setting for "post_max_size" is "%s".';
                    $message .= ' Please increase the php.ini setting and try again.';
                    throw new Exception(sprintf($message, $postMaxSize));
                }
            }

            if (!empty($files)) {
                $uploadDir = $this->alias->get("@media/{$dir}/");
                foreach ($files as $file) {
                    if ($file->getError() > 0) {
                        $error = match ($file->getError()) {
                            #0 => 'There is no error, the file uploaded with success',
                            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                            3 => 'The uploaded file was only partially uploaded',
                            4 => 'No file was uploaded',
                            6 => 'Missing a temporary folder',
                            7 => 'Failed to write file to disk.',
                            8 => 'A PHP extension stopped the file upload.',
                        };
                        $errors[] = $error;
                        continue;
                    }
                    $targetPath = $uploadDir . $file->getClientFilename();
                    try {
                        $file->moveTo($targetPath);
                    } catch (\Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                }
            } else {
                $errors[] = $this->t('Please choose at least one file.');
            }
        }

        if ($request->getMethod() === 'POST') {
            if (empty($errors) && empty($request->getHeader('X-Up-Validate'))) {
                return $this->redirect('media/index&dir=' . $dir);
            }
        }

        $status = empty($errors) ? 200 : 400;

        return $this->render('media/upload.twig', [
            'dir' => $dir,
            'errors' => $errors
        ], $status);
    }
}
