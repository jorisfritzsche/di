<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */
declare(strict_types=1);

namespace Di\Config;

class Caches extends AbstractConfig
{
    const CONFIG_FILE = 'caches.json';

    /**
     * @param array $cache
     *
     * @return self
     */
    public function addCache(array $cache) : self
    {
        $this->data = array_merge_recursive($this->data, $cache);

        return $this;
    }
}
