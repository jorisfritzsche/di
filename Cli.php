<?php

namespace Di;

class Cli
{
    /**
     * @var Config
     */
    protected $config;

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
     * @var bool
     */
    protected $clearConfig;

    /**
     * Cli constructor.
     *
     * @param Config $config
     *
     * @throws \Exception
     */
    public function __construct(Config $config)
    {
        $this->config = $config;

        /** This is a list of all available and accepted options. */
        $options = getopt(
            'hv',
            [
                'help',
                'verbose',
                'version',
                'add-rewrite:',
                'add-default-value:',
                'clear-config',
            ]
        );

        /**
         * @todo implement verbose operator as well as debugging options
         */
        if (isset($options['v']) || isset($options['verbose'])) {
            $this->verbose = true;
        }

        if (isset($options['h']) || isset($options['help'])) {
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

        if (isset($options['clear-config'])) {
            /** Prompt the user in order to make sure they know that this action cannot be undone. */
            echo "Are you sure you wish to clear the config? This action cannot be undone! [yN]";

            /** Read the response. */
            $handle = fopen ("php://stdin", "r");
            $line = fgets($handle);

            /** If the response is not 'Y' or 'y', abort the operation. */
            if(strcasecmp(trim($line), 'y') !== 0){
                echo "\e[31mABORTING!" . PHP_EOL;
                return;
            }

            /** Flag the config for clearing. */
            $this->clearConfig = true;
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
  \033[32m-h, --help\033[0m             Display this help message
  \033[32m-v, --verbose\033[0m          Display additional information while performing an action.
  \033[32m--version\033[0m              Display the current version
  \033[32m--add-rewrite\033[0m          The shortname used for the extension models and configuration, defaults to lowercase extension (eg. tig_queue)
  \033[32m--add-default-value\033[0m    The root of the Magento installation, defaults to current working directory, only supports full file path at the moment

USAGE;
    }

    /**
     * Add a rewrite to the config.
     */
    protected function addRewrite()
    {
        $rewrite = $this->addRewrite;

        $this->config->addRewrite($rewrite)->saveConfig();
    }

    /**
     * Add a rewrite to the config.
     */
    protected function addDefaultValue()
    {
        $defaultValue = $this->addDefaultValue;
        foreach ($defaultValue as $className => $parameters) {
            $this->config->addDefaultValue($className, $parameters);
        }

        $this->config->saveConfig();
    }

    /**
     * Clears the DI configuration JSON file.
     */
    protected function clearConfig()
    {
        $this->config->rewrites = new \StdClass();
        $this->config->defaultValues = new \StdClass();

        $this->config->saveConfig();
    }
}
