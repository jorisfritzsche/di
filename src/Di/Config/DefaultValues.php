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

class DefaultValues extends AbstractConfig
{
    const CONFIG_FILE = 'default_values.json';

    /**
     * @param string $className
     * @param array  $defaultValue
     *
     * @return self
     */
    public function addDefaultValue(string $className, array $defaultValue) : self
    {
        if (isset($this->data[$className])) {
            $defaultValue = array_merge($this->data[$className], $defaultValue);
        }

        $this->data[$className] = $defaultValue;
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
        $defaultValues = $this->data;
        if (isset($defaultValues[$className][$reflectionParameter->getName()])) {
            return $defaultValues[$className][$reflectionParameter->getName()];
        }

        return false;
    }
}
