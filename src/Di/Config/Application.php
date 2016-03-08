<?php

namespace Di\Config;

use Di\Exception;

class Application extends AbstractConfig
{
    const CONFIG_FILE = 'application.json';

    /**
     * @param string $env
     * @throws \Exception
     */
    public function setEnv(string $env)
    {
        $envConfig = new Environments();
        $availableEnvironments = array_keys($envConfig->data);
        if (!in_array($env, $availableEnvironments)) {
            $availableEnvironments = implode(', ', $availableEnvironments);
            throw new \Exception(
                "Requested environment is not available. Available environments: {$availableEnvironments}."
            );
        }

        $this->data['env'] = $env;

        $this->saveConfig();
    }
}