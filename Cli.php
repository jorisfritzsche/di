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
     * @var string
     */
    protected $setEnv;

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
                'set-env:',
                'clear-cache::',
                'clear-config::',
            ]
        );

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

        if (isset($options['set-env'])) {
            $env = $options['set-env'];

            $this->setEnv = $env;
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
            return;
        }

        if ($this->help) {
            $this->help();
            return;
        }

        if (!empty($this->addRewrite)) {
            $this->addRewrite();
            return;
        }

        if (!empty($this->addDefaultValue)) {
            $this->addDefaultValue();
            return;
        }

        if (!empty($this->setEnv)) {
            $this->setEnv();
            return;
        }

        if ($this->clearCaches) {
            $this->clearCache();
            return;
        }

        if ($this->clearConfig) {
            $this->clearConfig();
            return;
        }
    }

    /**
     * @return string
     */
    protected function getCurrentVersion() : string
    {
        $this->output("Outputting current version.");

        /** Get the composer.json file and parse it to find the current package's version. */
        $composerJson = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'composer.json');
        $composerJson = json_decode($composerJson, true);
        $this->output("Version parsed from composer.json: {$composerJson['version']}");

        return $composerJson['version'];
    }

    /**
     * Show the current version of the DI package.
     */
    protected function showVersion()
    {
        $this->output("Output:");
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
  \033[32m--set-env\033[0m              Set the application's environment variable.
  \033[32m--clear-cache\033[0m          Clear the cache files. This operation accepts a comma-separated list of caches to clear. If no caches are specified, all will be cleared.
  \033[32m--clear-config\033[0m         Clear the config JSON files. Beware: this action cannot be undone. This operation accepts a comma-separated list of configs to clear. If no configs are specified, all will be cleared.

USAGE;
    }

    /**
     * Add a rewrite to the config.
     */
    protected function addRewrite()
    {
        $this->loadClasses(['Config\\AbstractConfig', 'Config\\Rewrites']);

        $rewrite = $this->addRewrite;

        $config = new Config\Rewrites;
        $this->output("Rewriting class: " . key($rewrite) . " to: " . current($rewrite));
        $config->addRewrite($rewrite)->saveConfig();
    }

    /**
     * Add a rewrite to the config.
     */
    protected function addDefaultValue()
    {
        $this->loadClasses(['Config\\AbstractConfig', 'Config\\DefaultValues']);

        $defaultValue = $this->addDefaultValue;
        $config = new Config\DefaultValues();
        foreach ($defaultValue as $className => $parameters) {
            $this->output("Adding default value for class: " . $className);
            $this->output("Default values: " . var_export($parameters, true));
            $config->addDefaultValue($className, $parameters);
        }

        $config->saveConfig();
    }

    /**
     * Set the application's env parameter.
     */
    protected function setEnv()
    {
        $this->loadClasses(['Config\\AbstractConfig', 'Config\\Environments', 'Config\\Application']);

        $env = $this->setEnv;
        $config = new Config\Application();
        $config->setEnv($env);
    }

    /**
     * Clear the specified caches.
     *
     * @throws \Exception
     */
    protected function clearCache()
    {
        $this->loadClasses(['Config\\AbstractConfig', 'Config\\Caches', 'Cache\\AbstractCache']);

        $config = new Config\Caches();

        $cachesToClear = $this->clearCaches;

        if (!is_array($cachesToClear)) {
            $this->output("Clearing all caches.");
            $cachesToClear = array_keys($config->data);
        }

        foreach ($cachesToClear as $cacheType) {
            $this->clearCacheType($config, $cacheType);
        }
    }

    /**
     * Clear the specified cache.
     *
     * @param Config\Caches $config
     * @param string $cacheType
     *
     * @throws \Exception
     */
    protected function clearCacheType(Config\Caches $config, string $cacheType)
    {
        $this->output("Clearing cache type: " . $cacheType);
        if (!isset($config->data[$cacheType])) {
            throw new \Exception("Unknown cache type requested: {$cacheType}.");
        }

        $className = $config->data[$cacheType];
        $this->loadClasses([$className]);

        /** @var \Di\Cache\AbstractCache $cacheToClear */
        $cacheToClear = new $className;
        $cacheToClear->clear();
        $this->output("Cache type cleared: " . $cacheType);
    }

    /**
     * Clears the DI configuration JSON files.
     */
    protected function clearConfig()
    {
        $this->loadClasses(['Config\\AbstractConfig', 'Config\\Configs']);

        $config = new Config\Configs();

        $configsToClear = $this->clearConfig;

        if (!is_array($configsToClear)) {
            $this->output("Clearing all configs.");
            $configsToClear = array_keys($config->data);
        }

        foreach ($configsToClear as $configType) {
            $this->clearConfigType($config, $configType);
        }
    }

    /**
     * Clear the specified config.
     *
     * @param Config\Configs $config
     * @param string $configType
     *
     * @throws \Exception
     */
    protected function clearConfigType(Config\Configs $config, string $configType)
    {
        $this->output("Clearing config type: " . $configType);
        if (!isset($config->data[$configType])) {
            throw new \Exception("Unknown config type requested: {$configType}.");
        }

        $className = $config->data[$configType];
        $this->loadClasses([$className]);

        /** @var \Di\Config\AbstractConfig $configToClear */
        $configToClear = new $className;

        $configToClear->data = new \StdClass();
        $configToClear->saveConfig();
        $this->output("Config type cleared: " . $configType);
    }

    /**
     * Load the specified class files. This is required for the CLI, since the autloader will not be available.
     *
     * @todo move to custom CLI autoloader.
     *
     * @param array $classes
     */
    protected function loadClasses(array $classes)
    {
        $root = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        foreach ($classes as $class) {
            $this->output("Loading class: $class");

            $class = str_replace(['\\Di', 'Di'], '', $class);
            $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

            /** @noinspection PhpIncludeInspection */
            require_once($root . $file);
        }
    }

    /**
     * @param string $text
     * @param bool $force
     */
    public function output(string $text, $force = false)
    {
        if (!$force && !$this->verbose) {
            return;
        }

        echo "\033[32m" . $text . "\033[0m" . PHP_EOL;
    }
}
