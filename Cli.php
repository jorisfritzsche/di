<?php

namespace Di;

class Cli
{
    /**
     * @var string
     */
    protected $version;

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
     * Cli constructor.
     *
     * @param Config $config
     *
     * @throws \Exception
     */
    public function __construct(Config $config)
    {
        $this->config = $config;

        $options = getopt(
            'hv',
            [
                'help',
                'verbose',
                'version',
                'add-rewrite:',
                'add-default-value:'
            ]
        );

        $composerJson = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'composer.json');
        $composerJson = json_decode($composerJson, true);

        $this->version = $composerJson['version'];

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
            $rewrites = json_decode($options['add-rewrite']);
            if (!$rewrites) {
                throw new \Exception(
                    "Invalid rewrite given: {$options['add-rewrite']}. Rewrites must be given in JSON format."
                );
            }

            $this->addRewrite = $rewrites;
            return;
        }

        if (isset($options['add-default-value'])) {
            $rewrites = json_decode($options['add-default-value']);
            if (!$rewrites) {
                throw new \Exception(
                    "Invalid default value given: {$options['add-default-value']}. Default values must be given in" .
                    " JSON format."
                );
            }

            $this->addDefaultValue = $options['add-default-value'];
            return;
        }
    }

    public function execute()
    {
        if ($this->help) {
            $this->help();
        }
    }

    protected function help()
    {
        echo <<<USAGE
  ____ ___    ____ _     ___
 |  _ \_ _|  / ___| |   |_ _|
 | | | | |  | |   | |    | |
 | |_| | |  | |___| |___ | |
 |____/___|  \____|_____|___|

\033[32mDI CLI\033[0m version \033[33m{$this->version}\033[0m by \033[32mJoris Fritzsche.\033[0m

\033[33mOptions:\033[0m
  \033[32m-h, --help\033[0m             Display this help message
  \033[32m-v, --verbose\033[0m          Display additional information while performing an action.
  \033[32m--version\033[0m              Display the current version
  \033[32m--add-rewrite\033[0m          The shortname used for the extension models and configuration, defaults to lowercase extension (eg. tig_queue)
  \033[32m--add-default-value\033[0m    The root of the Magento installation, defaults to current working directory, only supports full file path at the moment

USAGE;
    }
}
