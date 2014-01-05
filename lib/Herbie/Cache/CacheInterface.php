<?php

namespace Herbie\Cache;

interface CacheInterface
{

    /**
     * @param string $id
     */
    public function get($id);

    /**
     * @param string $id
     * @param mixed $value
     */
    public function set($id, $value);

}