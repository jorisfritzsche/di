<?php

namespace Di;

class Config
{
    const CONFIG_DIR = 'config';
    const CONFIG_FILE = 'config.json';

    /**
     * @var string[]
     */
    protected $config;

    /**
     * @var string[];
     */
    public $rewrites;

    /**
     * @var string[];
     */
    public $defaultValues;

    /**
     * Initialize the configuration.
     */
    public function init()
    {
        $this->config = $this->getConfigJson();

        if (isset($this->config['rewrites'])) {
            $this->rewrites = $this->config['rewrites'];
        }

        if (isset($this->config['default_values'])) {
            $this->defaultValues = $this->config['default_values'];
        }
    }

    /**
     * @return string[]
     */
    protected function getConfigJson()
    {
        $file = __DIR__ . '/' . self::CONFIG_DIR . '/' . self::CONFIG_FILE;
        $config = [];
        if (file_exists($file)) {
            $json = file_get_contents($file);

            $config = json_decode($json, true);
        }

        return $config;
    }
}
