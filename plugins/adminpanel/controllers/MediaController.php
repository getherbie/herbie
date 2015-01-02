<?php

namespace herbie\plugin\adminpanel\controllers;

use Herbie;

class MediaController extends Controller
{

    public function addFolderAction($query, $request)
    {
        $dir = strtolower(trim($request->get('dir')));
        $name = strtolower(trim($request->get('name')));
        $path = $this->app['alias']->get('@media/' . $dir . '/' . $name);
        if(empty($name)) {
            $this->sendErrorHeader('Bitte einen Namen eingeben.');
        }
        if(is_dir($path)) {
            $this->sendErrorHeader('Ein gleichnamiger Ordner ist schon vorhanden.');
        }
        if(!@mkdir($path)) {
            $this->sendErrorHeader('Ordner konnte nicht erstellt werden.');
        }
        $query->add(['dir' => $dir]);
        return $this->mediaIndexAction($query, $request);
    }

    public function indexAction($query, $request)
    {
        $dir = $query->get('dir', '');
        $dir = str_replace(['../', '..', './', '.'], '', trim($dir, '/'));
        $path = $this->app['alias']->get('@media/' . $dir);
        $root = $this->app['alias']->get('@media');

        $iterator = null;
        if(is_dir($path)) {
            $directoryIterator = new Herbie\Iterator\DirectoryIterator($path, $root);
            $iterator = new Herbie\Iterator\DirectoryDotFilter($directoryIterator);
        }

        return $this->render('media/index.twig', [
            'iterator' => $iterator,
            'dir' => $dir,
            'parentDir' => str_replace('.', '', dirname($dir)),
        ]);
    }

    public function deleteAction($query, $request)
    {
        $path = $request->get('file');
        $path = str_replace(['../', '..', './'], '', trim($path, '/'));
        $absPath = $this->app['alias']->get('@media/' . $path);
        $name = basename($absPath);

        if(is_file($absPath) && !@unlink($absPath)) {
            $this->sendErrorHeader("Datei {$name} konnte nicht gelöscht werden.");
        } elseif(is_dir($absPath) && !@rmdir($absPath)) {
            if(count(scandir($absPath)) >= 2) {
                $this->sendErrorHeader("Ordner {$name} enthält Dateien und konnte nicht gelöscht werden.");
            }
            $this->sendErrorHeader("Ordner {$name} konnte nicht gelöscht werden.");
        }
        header('Content-Type: application/json');
        echo json_encode(true);
        exit;
    }

    public function uploadAction($query, $request)
    {
        $data = array();
        $dir = strtolower(trim($request->get('dir')));

        if(!empty($_FILES)) {
            $files = array();

            $uploaddir = $this->app['alias']->get("@media/{$dir}/");
            foreach($_FILES as $file)
            {
                if(move_uploaded_file($file['tmp_name'], $uploaddir . basename($file['name']))) {
                    $files[] = $uploaddir . $file['name'];
                } else {
                    $this->sendErrorHeader('Beim Upload ist ein Fehler aufgetreten.');
                }
            }
            $data = array('files' => $files);
        } else {
            $this->sendErrorHeader('Bitte eine oder mehrere Dateien auswählen.');
        }

        $query->add(['dir' => $dir]);
        $data['html'] = $this->indexAction($query, $request);

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

}