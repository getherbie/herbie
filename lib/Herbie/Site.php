<?php

namespace Herbie;

/**
 * Stores the site.
 *
 * @author Thomas Breuss <thomas.breuss@zephir.ch>
 */
class Site
{

    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function getTime()
    {
        return date('c');
    }

    public function getData()
    {
        return $this->app['data'];
    }

    public function getMenu()
    {
        return $this->app['menu'];
    }

    public function getTree()
    {
        return $this->app['tree'];
    }

    public function getPosts()
    {
        return $this->app['posts'];
    }

    public function getRootPath()
    {
        return $this->app['rootPath'];
    }

    public function getLastCreated()
    {
        $lastCreated = 0;
        foreach($this->app['menu'] AS $item) {
            $modified = strtotime($item->getCreated());
            if($modified > $lastCreated) {
                $lastCreated = $modified;
            }
        }
        return date('c', $lastCreated);
    }

    public function getLastModified()
    {
        $lastModified = 0;
        foreach($this->app['menu'] AS $item) {
            $modified = strtotime($item->getModified());
            if($modified > $lastModified) {
                $lastModified = $modified;
            }
        }
        return date('c', $lastModified);
    }


}