<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */
declare(strict_types=1);

namespace Di\Config;

class DefaultValues extends AbstractConfig
{
    const CONFIG_FILE = 'default_values.json';

    /**
     * Add a new default value or replace an existing one.
     *
     * @param string $className
     * @param array  $defaultValue
     *
     * @return self
     *
     * @todo validate the default value.
     */
    public function addDefaultValue(string $className, array $defaultValue) : self
    {
        if (isset($this->data[$className])) {
            $defaultValue = array_merge_recursive($this->data[$className], $defaultValue);
        }

        $this->data[$className] = $defaultValue;

        return $this;
    }

    /**
     * Remove an existing default value.
     *
     * @param string $className
     *
     * @return DefaultValues
     *
     * @todo Allow for removing specific parameters per class.
     */
    public function removeDefaultValue(string $className) : self
    {
        if (isset($this->data[$className])) {
            unset($this->data[$className]);
        }

        return $this;
    }

    /**
     * Try to find a default value for this parameter from the DI config.
     *
     * @param string $className
     * @param string $parameterName
     *
     * @return false|mixed
     */
    public function getDefaultDiValue(string $className, string $parameterName)
    {
        /* Get the parameter's declaring class' class name and make sure it starts in the root namespace. */
        if (strpos($className, '\\') !== 0) {
            $className = '\\'.$className;
        }

        /* Check if a default value is defined for this parameter and return it. */
        $defaultValues = $this->data;
        if (isset($defaultValues[$className][$parameterName])) {
            return $defaultValues[$className][$parameterName];
        }

        return false;
    }
}
