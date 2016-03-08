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
     * @param array $rewrite
     *
     * @return $this
     */
    public function addRewrite(array $rewrite)
    {
        $this->rewrites = array_merge_recursive($this->rewrites, $rewrite);

        return $this;
    }

    /**
     * @param string $className
     * @param array  $defaultValue
     *
     * @return $this
     */
    public function addDefaultValue(string $className, array $defaultValue)
    {
        if (isset($this->defaultValues[$className])) {
            $defaultValue = array_merge($this->defaultValues[$className], $defaultValue);
        }
        $this->defaultValues[$className] = $defaultValue;

        return $this;
    }

    /**
     * @return string
     */
    protected function getConfigFile()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . self::CONFIG_DIR . DIRECTORY_SEPARATOR . self::CONFIG_FILE;

        return $file;
    }

    /**
     * @return string[]
     */
    protected function getConfigJson()
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
     * Please note: this overwrites the actual file, not the cache!
     *
     * @return $this
     */
    public function saveConfig()
    {
        $file = $this->getConfigFile();

        $this->mergeConfig();
        $config = json_encode($this->config, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);

        file_put_contents($file, $config);

        return $this;
    }

    /**
     * @return $this
     */
    protected function mergeConfig()
    {
        $this->config = [
            'rewrites' => $this->rewrites,
            'default_values' => $this->defaultValues,
        ];

        return $this;
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
