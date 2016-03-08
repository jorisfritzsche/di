<?php

namespace Di\Config;

class Caches extends AbstractConfig
{
    const CONFIG_FILE = 'caches.json';

    /**
     * @param array $cache
     *
     * @return $this
     */
    public function addCache(array $cache)
    {
        $this->data = array_merge_recursive($this->data, $cache);

        return $this;
    }
}