<?php

namespace Herbie\Cache;

use Exception;
use LogicException;

class DataCache implements CacheInterface
{
    /**
     * @var boolean
     */
    protected $enable;

    /**
     * @var string
     */
    protected $dir;

    /**
     * @var int
     */
    protected $expire;

    /**
     * @param array $options
     */
    public function __construct(array $options=[])
    {
        $this->enable = null;
        $this->dir = null;
        $this->expire = 60*60*24;
        $this->setOptions($options);
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        foreach($options AS $key=>$value) {
            $this->$key = $value;
        }
    }

    /**
     * @param string $id
     * @return mixed|boolean
     */
    public function get($id)
    {
        $filename = $this->makeFilename($id);
        if (file_exists($filename) && (time() - $this->expire < filemtime($filename))) {
            $serialized = file_get_contents($filename);
            return unserialize($serialized);
        }
        return false;
    }

    /**
     * @param string $id
     * @param mixed $value
     * @return boolean
     * @throws Exception
     */
    public function set($id, $value)
    {
        $filename = $this->makeFilename($id);
        $written = file_put_contents($filename, serialize($value));
        if($written === false) {
            throw new Exception('Could not write to data cache file', 500);
        }
        return true;
    }

    /**
     * @param string $id
     * @return string
     */
    protected function makeFilename($id)
    {
        $id = md5($id);
        return sprintf('%s/%s.%s', $this->dir, $id, 'cache');
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws LogicException
     */
    public function __set($name, $value)
    {
        $message = sprintf('Property "%s" does not exist in class %s.', $name, __CLASS__);
        throw new LogicException($message);
    }

}
