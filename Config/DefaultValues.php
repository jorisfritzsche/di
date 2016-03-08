<?php

namespace Di\Config;

class DefaultValues extends AbstractConfig
{
    const CONFIG_FILE = 'default_values.json';

    /**
     * @param string $className
     * @param array  $defaultValue
     *
     * @return $this
     */
    public function addDefaultValue(string $className, array $defaultValue)
    {
        if (isset($this->config[$className])) {
            $defaultValue = array_merge($this->config[$className], $defaultValue);
        }

        $this->config[$className] = $defaultValue;
        return $this;
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
        $defaultValues = $this->config;
        if (isset($defaultValues[$className][$reflectionParameter->getName()])) {
            return $defaultValues[$className][$reflectionParameter->getName()];
        }

        return false;
    }
}