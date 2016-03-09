<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */

declare(strict_types = 1);

namespace Di\Config;

use Di\FileLoader\Json;

class Application extends AbstractConfig
{
    const CONFIG_FILE = 'application.json';

    /**
     * @var Environments
     */
    protected $environments;

    /**
     * Application constructor.
     * @param Json $fileLoader
     * @param Environments $environments
     */
    public function __construct(Json $fileLoader, Environments $environments)
    {
        parent::__construct($fileLoader);

        $this->environments = $environments;
    }

    /**
     * @param string $env
     *
     * @return self
     *
     * @throws \Exception
     */
    public function setEnv(string $env) : self
    {
        /** Get the available environments. */
        $availableEnvironments = array_keys($this->environments->data);
        if (!in_array($env, $availableEnvironments)) {
            /**
             * If the desired environment is not available, throw an exception with information on which environments
             * are available.
             */
            $availableEnvironments = implode(', ', $availableEnvironments);
            throw new \Exception(
                "Requested environment is not available. Available environments: {$availableEnvironments}."
            );
        }

        /** Set the environment. */
        $this->data['env'] = $env;
        return $this;
    }
}
