<?php

/*
 * This file is part of the Di package.
 *
 * (c) Joris Fritzsche <joris.fritzsche@outlook.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

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
