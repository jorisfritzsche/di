<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */

declare(strict_types = 1);

namespace Di;

class Cli
{
    /** Available output levels. */
    const OUTPUT_LEVEL_NOTICE  = 1;
    const OUTPUT_LEVEL_WARNING = 2;
    const OUTPUT_LEVEL_ERROR   = 3;

    /**
     * @var Container
     */
    protected $container;

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
     * @param Container $container
     *
     * @throws Cli\Exception
     */
    public function __construct(Container $container = null)
    {
        if (!$container) {
            $this->container = new Container();
        }

        /**
         * This is a list of all available and accepted options.
         * @todo get available operations from config file with callbacks.
         */
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

        $this->setOperations($options);
    }

    /**
     * Check which operation was requested and validate the parameters provided.
     *
     * @param array $options
     * @throws Cli\Exception
     */
    protected function setOperations(array $options)
    {
        /** Check if verbose mode was requested. */
        if (isset($options['v']) || isset($options['verbose'])) {
            $this->verbose = true;
            /**
             * Unlike the other checks in this method, verbose mode is not an operation and can therefore be used in
             * conjunction with any operation.
             */
        }

        /** Check if the 'help' operation was requested. */
        if (isset($options['h']) || isset($options['help'])) {
            $this->help = true;
            return;
        }

        /** Check if the 'version' operation was requested. */
        if (isset($options['version'])) {
            $this->showVersion = true;
            return;
        }

        /** Check if the 'add-rewrite' operation was requested. */
        if (isset($options['add-rewrite'])) {
            /** Add-rewrite requires a JSON-encoded string with the rewrite that needs to be added. */
            $rewrites = json_decode($options['add-rewrite'], true);
            /** If no rewrites were supplied, throw an error. */
            if (!$rewrites) {
                throw new Cli\Exception(
                    "Invalid rewrite given: {$options['add-rewrite']}. Rewrites must be given in JSON format."
                );
            }

            $this->addRewrite = $rewrites;
            return;
        }

        /** Check if the 'add-default-value' operation was requested. */
        if (isset($options['add-default-value'])) {
            /** Add-default-value requires a JSON-encoded string with the default value that needs to be added. */
            $defaultValues = json_decode($options['add-default-value'], true);
            /** If no default values were supplied, throw an error. */
            if (!$defaultValues) {
                throw new Cli\Exception(
                    "Invalid default value given: {$options['add-default-value']}. Default values must be given in" .
                    " JSON format."
                );
            }

            $this->addDefaultValue = $defaultValues;
            return;
        }

        /** Check if the 'set-env' operation was requested. */
        if (isset($options['set-env'])) {
            /**
             * Set-env requires an environment to be set. Whether the requested environment was valid will be checked
             * later.
             */
            $env = $options['set-env'];

            $this->setEnv = $env;
            return;
        }

        /** Check if the 'clear-cache' operation was requested. */
        if (isset($options['clear-cache'])) {
            /** Get the specified caches to be cleared. If no caches are specified, clear them all. */
            if (!empty($options['clear-cache'])) {
                $caches = explode(',', $options['clear-cache']);
            } else {
                $caches = true;
            }

            $this->clearCaches = $caches;
            return;
        }

        /** Check if the 'clear-config' operation was requested. */
        if (isset($options['clear-config'])) {
            /** Prompt the user in order to make sure they know that this action cannot be undone. */
            $this->output(
                "Are you sure you wish to clear the config? This action cannot be undone! [yN]",
                self::OUTPUT_LEVEL_WARNING,
                true
            );

            /** Read the response. */
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);

            /** If the response is not 'Y' or 'y', abort the operation. */
            if (strcasecmp(trim($line), 'y') !== 0) {
                $this->output("ABORTING!", self::OUTPUT_LEVEL_ERROR, true);
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

        /** If no valid operations were found, show a message that indicates such. */
        $this->output(
            "Invalid or no operation specified. Use --help for possible operations.",
            self::OUTPUT_LEVEL_WARNING,
            true
        );
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
     * Get the current version.
     *
     * The version will be dynamically read from the composer.json file.
     *
     * @return string
     */
    protected function getCurrentVersion() : string
    {
        $this->output("Outputting current version.", self::OUTPUT_LEVEL_NOTICE);

        /** Get the composer.json file and parse it to find the current package's version. */
        $composerJson = file_get_contents(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'composer.json');
        $composerJson = json_decode($composerJson, true);
        $this->output("Version parsed from composer.json: {$composerJson['version']}", self::OUTPUT_LEVEL_NOTICE);

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
        // @codingStandardsIgnoreStart
        /** Ignoring PHP CS, because of line length limitations. */
        $this->showVersion();
        echo <<<USAGE

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
        // @codingStandardsIgnoreEnd
    }

    /**
     * Add a rewrite to the config.
     */
    protected function addRewrite()
    {
        $rewrite = $this->addRewrite;

        $config = $this->container->create("Di\\Config\\Rewrites");
        $this->output("Rewriting class: " . key($rewrite) . " to: " . current($rewrite), self::OUTPUT_LEVEL_NOTICE);
        $config->addRewrite($rewrite)->saveConfig();
    }

    /**
     * Add a rewrite to the config.
     */
    protected function addDefaultValue()
    {
        $defaultValue = $this->addDefaultValue;
        $config = $this->container->create("Di\\Config\\DefaultValues");

        /** Loop through all new default values and add them individually. */
        foreach ($defaultValue as $className => $parameters) {
            $this->output("Adding default value for class: " . $className, self::OUTPUT_LEVEL_NOTICE);
            $this->output("Default values: " . var_export($parameters, true), self::OUTPUT_LEVEL_NOTICE);
            $config->addDefaultValue($className, $parameters);
        }

        $config->saveConfig();
    }

    /**
     * Set the application's env parameter.
     */
    protected function setEnv()
    {
        $env = $this->setEnv;

        /** @var \Di\Config\Application $config */
        $config = $this->container->create("Di\\Config\\Application");
        $config->setEnv($env);
        $config->saveConfig();
    }

    /**
     * Clear the specified caches.
     *
     * @throws Cli\Exception
     */
    protected function clearCache()
    {
        /** @var Config\Caches $config */
        $config = $this->container->create("Di\\Config\\Caches");

        $cachesToClear = $this->clearCaches;

        /** If no specific cache was requested, get a list of all caches that can be cleared. */
        if (!is_array($cachesToClear)) {
            $this->output("Clearing all caches.", self::OUTPUT_LEVEL_NOTICE);
            $cachesToClear = array_keys($config->data);
        }

        /** Clear each cache type. */
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
     * @throws Cli\Exception
     */
    protected function clearCacheType(Config\Caches $config, string $cacheType)
    {
        $this->output("Clearing cache type: " . $cacheType, self::OUTPUT_LEVEL_NOTICE);
        if (!isset($config->data[$cacheType])) {
            throw new Cli\Exception("Unknown cache type requested: {$cacheType}.");
        }

        $className = $config->data[$cacheType];

        /** @var \Di\Cache\AbstractCache $cacheToClear */
        $cacheToClear = $this->container->create($className);
        $cacheToClear->clear();
        $this->output("Cache type cleared: " . $cacheType, self::OUTPUT_LEVEL_NOTICE);
    }

    /**
     * Clears the DI configuration JSON files.
     */
    protected function clearConfig()
    {
        /** @var Config\Configs $config */
        $config = $this->container->create("Di\\Config\\Configs");

        $configsToClear = $this->clearConfig;

        /** If no specific config was requested, get a list of all configs that can be cleared. */
        if (!is_array($configsToClear)) {
            $this->output("Clearing all configs.", self::OUTPUT_LEVEL_NOTICE);
            $configsToClear = array_keys($config->data);
        }

        /** Clear each config type. */
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
     * @throws Cli\Exception
     */
    protected function clearConfigType(Config\Configs $config, string $configType)
    {
        $this->output("Clearing config type: " . $configType, self::OUTPUT_LEVEL_NOTICE);
        if (!isset($config->data[$configType])) {
            throw new Cli\Exception("Unknown config type requested: {$configType}.");
        }

        $className = $config->data[$configType];

        /** @var \Di\Config\AbstractConfig $configToClear */
        $configToClear = $this->container->create($className);

        $configToClear->data = $this->container->create("\\StdCLass");
        $configToClear->saveConfig();
        $this->output("Config type cleared: " . $configType, self::OUTPUT_LEVEL_NOTICE);
    }

    /**
     * Output a string.
     *
     * If the $force parameter is false, the string will only be output when verbose mode is on.
     *
     * @param string   $text
     * @param null|int $level
     * @param bool     $force
     */
    public function output(string $text, $level = null, $force = false)
    {
        if (!$force && !$this->verbose) {
            return;
        }

        /** Determine the text colour for the specified output level. */
        $colour = "\033[34m";
        switch ($level) {
            case self::OUTPUT_LEVEL_NOTICE:
                $colour = "\033[32m";
                break;
            case self::OUTPUT_LEVEL_WARNING:
                $colour = "\033[33m";
                break;
            case self::OUTPUT_LEVEL_ERROR:
                $colour = "\033[31m";
                break;
        }

        echo $colour . $text . "\033[0m" . PHP_EOL;
    }
}
