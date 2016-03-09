<?php

/*
 * This file is part of the Di package.
 *
 * (c) Joris Fritzsche <joris.fritzsche@outlook.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Di\Config;

class Application extends AbstractConfig
{
    const CONFIG_FILE = 'application.json';

    /**
     * @param string $env
     *
     * @return self
     *
     * @throws \Exception
     */
    public function setEnv(string $env) : self
    {
        $envConfig = new Environments();

        /** Get the available environments. */
        $availableEnvironments = array_keys($envConfig->data);
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
