<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */

declare(strict_types = 1);

namespace Di\Config;

abstract class AbstractConfig
{
    const CONFIG_DIR = 'etc';
    const CONFIG_FILE = '';

    /**
     * @var bool
     */
    protected $mergeConfig = false;

    /**
     * @var string[]
     */
    public $data;

    /**
     * Initialize the configuration.
     */
    public function __construct()
    {
        $this->data = $this->getConfigJson();
    }

    /**
     * @return string
     */
    protected function getConfigFile() : string
    {
        $file = dirname(dirname(dirname(dirname(__FILE__))))
            . DIRECTORY_SEPARATOR
            . static::CONFIG_DIR
            . DIRECTORY_SEPARATOR
            . static::CONFIG_FILE;

        return $file;
    }

    /**
     * @return string[]
     */
    protected function getConfigJson() : array
    {
        $file = $this->getConfigFile();
        $config = [];
        if (file_exists($file)) {
            $json = file_get_contents($file);

            $config = json_decode($json, true);
        }

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
        $file = $this->getConfigFile();

        $config = json_encode($this->data, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);

        file_put_contents($file, $config);

        return $this;
    }
}