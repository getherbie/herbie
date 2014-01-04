<?php

namespace Herbie\Cache;


class DummyCache implements CacheInterface
{

    /**
     * @param string $id
     * @return boolean
     */
    public function get($id)
    {
        return false;
    }

    /**
     * @param string $id
     * @param string $value
     * @return boolean
     */
    public function set($id, $value)
    {
        return false;
    }

}
