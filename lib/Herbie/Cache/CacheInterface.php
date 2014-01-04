<?php

namespace Herbie\Cache;

interface CacheInterface
{

    public function get($id);

    public function set($id, $value);

}