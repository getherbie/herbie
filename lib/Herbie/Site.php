<?php

namespace Herbie;


/**
 * Stores the site.
 *
 * @author Thomas Breuss <thomas.breuss@zephir.ch>
 */
class Site
{

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return string
     */
    public function getTime()
    {
        return date('c');
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->app['data'];
    }

    /**
     * @return MenuCollection
     */
    public function getMenu()
    {
        return $this->app['menu'];
    }

    /**
     * @return MenuTree
     */
    public function getTree()
    {
        return $this->app['tree'];
    }

    /**
     * @return PostCollection
     */
    public function getPosts()
    {
        return $this->app['posts'];
    }

    /**
     * @return string
     */
    public function getRootPath()
    {
        return $this->app['rootPath'];
    }

    /**
     * @return string
     */
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

    /**
     * @return string
     */
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