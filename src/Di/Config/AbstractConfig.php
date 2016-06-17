<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */
declare(strict_types=1);

namespace Di\Config;

use Di\FileLoader\Json;

abstract class AbstractConfig
{
    /** The config file and directory where the config is stored. */
    const CONFIG_DIR = 'etc';
    const CONFIG_FILE = '';

    /**
     * @var Json
     */
    protected $fileLoader;

    /**
     * @var string[]
     */
    public $data;

    /**
     * Initialize the configuration.
     *
     * @param Json $fileLoader
     */
    public function __construct(Json $fileLoader)
    {
        $this->fileLoader = $fileLoader;

        $this->data = $this->getConfigJson();
    }

    /**
     * @return string[]
     */
    protected function getConfigJson() : array
    {
        $config = $this->fileLoader->load(static::CONFIG_FILE);

        return $config;
    }

    /**
     * Save the current config to the configuration file.
     *
     * Please note: this overwrites the actual config file, not the cache!
     *
     * @return self
     */
    public function saveConfig() : self
    {
        $this->fileLoader->save(static::CONFIG_FILE, $this->data);

        return $this;
    }
}
