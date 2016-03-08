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

    /**
     * Process any rewrites found in the DI config.
     *
     * @param \ReflectionType $type
     *
     * @return \ReflectionType|string
     */
    public function processRewrites(\ReflectionType $type)
    {
        /** Get the type's class name and make sure it starts in the root namespace. */
        $stringType = (string) $type;
        if (strpos($stringType, '\\') !== 0) {
            $stringType = '\\' . $stringType;
        }

        /** Check for any rewrites and return the rewritten class name if available. */
        if (isset($this->rewrites[$stringType])) {
            $type = $this->rewrites[$stringType];
        }

        return $type;
    }

    /**
     * Try to find a default value for this parameter from the DI config.
     *
     * @param \ReflectionParameter $reflectionParameter
     *
     * @return mixed|false
     */
    public function getDefaultDiValue(\ReflectionParameter $reflectionParameter)
    {
        /** Get the parameter's declaring class' class name and make sure it starts in the root namespace. */
        $className = $reflectionParameter->getDeclaringClass()->getName();
        if (strpos($className, '\\') !== 0) {
            $className = '\\' . $className;
        }

        /** Check if a default value is defined for this parameter and return it. */
        $defaultValues = $this->defaultValues;
        if (isset($defaultValues[$className][$reflectionParameter->getName()])) {
            return $defaultValues[$className][$reflectionParameter->getName()];
        }

        return false;
    }
}
