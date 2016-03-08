<?php

namespace Di;

class Cli
{
    /**
     * @var bool
     */
    protected $help;

    /**
     * @var bool
     */
    protected $verbose;

    /**
     * @var bool
     */
    protected $showVersion;

    /**
     * @var array
     */
    protected $addRewrite;

    /**
     * @var array
     */
    protected $addDefaultValue;

    /**
     * @var array
     */
    protected $clearCaches;

    /**
     * @var bool
     */
    protected $clearConfig;

    /**
     * Cli constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        /** This is a list of all available and accepted options. */
        $options = getopt(
            'hv',
            [
                'help',
                'verbose',
                'version',
                'add-rewrite:',
                'add-default-value:',
                'clear-cache::',
                'clear-config::',
            ]
        );

        /**
         * @todo implement verbose operator as well as debugging options
         */
        if (isset($options['v']) || isset($options['verbose'])) {
            $this->verbose = true;
        }

        if (isset($options['h']) || isset($options['help']) || empty($options)) {
            $this->help = true;
            return;
        }

        if (isset($options['version'])) {
            $this->showVersion = true;
            return;
        }

        if (isset($options['add-rewrite'])) {
            $rewrites = json_decode($options['add-rewrite'], true);
            if (!$rewrites) {
                throw new \Exception(
                    "Invalid rewrite given: {$options['add-rewrite']}. Rewrites must be given in JSON format."
                );
            }

            $this->addRewrite = $rewrites;
            return;
        }

        if (isset($options['add-default-value'])) {
            $defaultValues = json_decode($options['add-default-value'], true);
            if (!$defaultValues) {
                throw new \Exception(
                    "Invalid default value given: {$options['add-default-value']}. Default values must be given in" .
                    " JSON format."
                );
            }

            $this->addDefaultValue = $defaultValues;
            return;
        }

        if (isset($options['clear-cache'])) {
            /** get the specified caches to be cleared. If no caches are specified, clear them all. */
            if (!empty($options['clear-cache'])) {
                $caches = explode(',', $options['clear-cache']);
            } else {
                $caches = true;
            }

            $this->clearCaches = $caches;
            return;
        }

        if (isset($options['clear-config'])) {
            /** Prompt the user in order to make sure they know that this action cannot be undone. */
            echo "Are you sure you wish to clear the config? This action cannot be undone! [yN]";

            /** Read the response. */
            $handle = fopen ("php://stdin", "r");
            $line = fgets($handle);

            /** If the response is not 'Y' or 'y', abort the operation. */
            if(strcasecmp(trim($line), 'y') !== 0){
                echo "\e[31mABORTING!\033[0m" . PHP_EOL;
                return;
            }

            /** get the specified configs to be cleared. If no configs are specified, clear them all. */
            if (!empty($options['clear-config'])) {
                $configs = explode(',', $options['clear-config']);
            } else {
                $configs = true;
            }

            /** Flag the config for clearing. */
            $this->clearConfig = $configs;
            return;
        }
    }

    /**
     * Execute the operation.
     */
    public function execute()
    {
        if ($this->showVersion) {
            $this->showVersion();
        }

        if ($this->help) {
            $this->help();
        }

        if (!empty($this->addRewrite)) {
            $this->addRewrite();
        }

        if (!empty($this->addDefaultValue)) {
            $this->addDefaultValue();
        }

        if ($this->clearCaches) {
            $this->clearCache();
        }

        if ($this->clearConfig) {
            $this->clearConfig();
        }
    }

    /**
     * @return string
     */
    protected function getCurrentVersion()
    {
        /** Get the composer.json file and parse it to find the current package's version. */
        $composerJson = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'composer.json');
        $composerJson = json_decode($composerJson, true);

        return $composerJson['version'];
    }

    /**
     * Show the current version of the DI package.
     */
    protected function showVersion()
    {
        echo <<<VERSION
  ____ ___    ____ _     ___
 |  _ \_ _|  / ___| |   |_ _|
 | | | | |  | |   | |    | |
 | |_| | |  | |___| |___ | |
 |____/___|  \____|_____|___|

\033[32mDI CLI\033[0m version \033[33m{$this->getCurrentVersion()}\033[0m by \033[32mJoris Fritzsche.\033[0m

VERSION;
    }

    /**
     * Show usage information.
     */
    protected function help()
    {
        echo <<<USAGE
  ____ ___    ____ _     ___
 |  _ \_ _|  / ___| |   |_ _|
 | | | | |  | |   | |    | |
 | |_| | |  | |___| |___ | |
 |____/___|  \____|_____|___|

\033[32mDI CLI\033[0m version \033[33m{$this->getCurrentVersion()}\033[0m by \033[32mJoris Fritzsche.\033[0m

\033[33mOptions:\033[0m
  \033[32m-h, --help\033[0m             Display this help message.
  \033[32m-v, --verbose\033[0m          Display additional information while performing an action.
  \033[32m--version\033[0m              Display the current version.
  \033[32m--add-rewrite\033[0m          Add new rewrites to the config.json file.
  \033[32m--add-default-value\033[0m    Add new default values to the config.json file.
  \033[32m--clear-cache\033[0m         Clear the cache files. This operation accepts a comma-separated list of caches to clear. If no caches are specified, all will be cleared.
  \033[32m--clear-config\033[0m         Clear the config JSON files. Beware: this action cannot be undone. This operation accepts a comma-separated list of configs to clear. If no configs are specified, all will be cleared.

USAGE;
    }

    /**
     * Add a rewrite to the config.
     */
    protected function addRewrite()
    {
        $configPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR;
        /** @noinspection PhpIncludeInspection */
        require_once($configPath . 'AbstractConfig.php');
        /** @noinspection PhpIncludeInspection */
        require_once($configPath . 'Rewrites.php');

        $rewrite = $this->addRewrite;

        $config = new Config\Rewrites;
        $config->addRewrite($rewrite)->saveConfig();
    }

    /**
     * Add a rewrite to the config.
     */
    protected function addDefaultValue()
    {
        $configPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR;
        /** @noinspection PhpIncludeInspection */
        require_once($configPath . 'AbstractConfig.php');
        /** @noinspection PhpIncludeInspection */
        require_once($configPath . 'DefaultValues.php');

        $defaultValue = $this->addDefaultValue;
        $config = new Config\DefaultValues();
        foreach ($defaultValue as $className => $parameters) {
            $config->addDefaultValue($className, $parameters);
        }

        $config->saveConfig();
    }

    /**
     * Clear the specified caches.
     *
     * @throws \Exception
     */
    protected function clearCache()
    {
        $configPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR;
        $cachePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Cache' . DIRECTORY_SEPARATOR;
        /** @noinspection PhpIncludeInspection */
        require_once($configPath . 'AbstractConfig.php');
        /** @noinspection PhpIncludeInspection */
        require_once($configPath . 'Caches.php');
        /** @noinspection PhpIncludeInspection */
        require_once($cachePath . 'AbstractCache.php');

        $config = new Config\Caches();

        $cachesToClear = $this->clearCaches;

        if (!is_array($cachesToClear)) {
            $cachesToClear = array_keys($config->data);
        }

        $cachePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Cache' . DIRECTORY_SEPARATOR;
        foreach ($cachesToClear as $cacheType) {
            $this->clearCacheType($config, $cacheType, $cachePath);
        }
    }

    /**
     * Clear the specified cache.
     *
     * @param Config\Caches $config
     * @param string $cacheType
     * @param string $cachePath
     * @throws \Exception
     */
    protected function clearCacheType(Config\Caches $config, string $cacheType, string $cachePath)
    {
        if (!isset($config->data[$cacheType])) {
            throw new \Exception("Unknown cache type requested: {$cacheType}.");
        }

        $className = $config->data[$cacheType];
        $classNameParts = explode('\\', $className);
        $fileName = end($classNameParts) . '.php';

        /** @noinspection PhpIncludeInspection */
        require_once($cachePath . $fileName);

        /** @var \Di\Cache\AbstractCache $cacheToClear */
        $cacheToClear = new $className;
        $cacheToClear->clear();
    }

    /**
     * Clears the DI configuration JSON files.
     */
    protected function clearConfig()
    {
        $configPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR;
        /** @noinspection PhpIncludeInspection */
        require_once($configPath . 'AbstractConfig.php');
        /** @noinspection PhpIncludeInspection */
        require_once($configPath . 'Configs.php');

        $config = new Config\Configs();

        $configsToClear = $this->clearConfig;

        if (!is_array($configsToClear)) {
            $configsToClear = array_keys($config->data);
        }

        foreach ($configsToClear as $configType) {
            $this->clearConfigType($config, $configType, $configPath);
        }
    }

    /**
     * Clear the specified config.
     *
     * @param Config\Configs $config
     * @param string $configType
     * @param string $configPath
     * @throws \Exception
     */
    protected function clearConfigType(Config\Configs $config, string $configType, string $configPath)
    {
        if (!isset($config->data[$configType])) {
            throw new \Exception("Unknown config type requested: {$configType}.");
        }

        $className = $config->data[$configType];
        $classNameParts = explode('\\', $className);
        $fileName = end($classNameParts) . '.php';

        /** @noinspection PhpIncludeInspection */
        require_once($configPath . $fileName);

        /** @var \Di\Config\AbstractConfig $configToClear */
        $configToClear = new $className;

        $configToClear->data = new \StdClass();
        $configToClear->saveConfig();
    }
}
