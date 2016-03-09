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

namespace Di;

class Factory
{
    /**
     * @var Config\Rewrites
     */
    public $rewrites;

    /**
     * @var Config\DefaultValues
     */
    public $defaultValues;

    /**
     * @var Cache\Classes
     */
    public $cache;

    /**
     * @var array
     */
    protected $processingClasses = [];

    /**
     * Factory constructor.
     */
    public function __construct()
    {
        $rewrites      = new Config\Rewrites();
        $defaultValues = new Config\DefaultValues();
        $cache         = new Cache\Classes();

        $this->rewrites      = $rewrites;
        $this->defaultValues = $defaultValues;
        $this->cache         = $cache;
    }

    /**
     * Get the requested class with DI applied.
     *
     * @param string $className
     * @param array  $givenArguments
     *
     * @return object
     *
     * @throws Exception
     */
    public function get(string $className, array $givenArguments = [])
    {
        /** Check if the requested class can be autoloaded. */
        if (!class_exists($className)) {
            throw new Exception("Class {$className} does not exist.");
        }

        /** Make sure this class is not already being processed, in order to prevent infinite recursion. */
        if (isset($this->processingClasses[$className])) {
            throw new Exception(
                "Class {$className} is already being processed. This probably means that one or more classes require "
                . "each other, causing an infinite loop."
            );
        }

        /** Add the requested class name to the array of classes being processed. */
        $this->processingClasses[$className] = true;

        /** Get the ReflectionClass instance for the requested class name. */
        $reflector = $this->getReflector($className);
        /** Get the ReflectionArgument instance for the class' constructor, if it has one. */
        $constructor = $this->getConstructor($reflector);

        /** if no constructor is defined, simply return a new instance of the class. */
        if (!$constructor) {
            return $this->createNewInstance($reflector, null, $className);
        }

        /** If the constructor has not parameters, simply return a new instance of the class. */
        if ($constructor->getNumberOfParameters() == 0) {
            return $this->createNewInstance($reflector, null, $className);
        }

        $reflectionParameters = $this->cache->retrieve($className);
        if (!$reflectionParameters) {
            /** Get the paremeters of the constructor. */
            $reflectionParameters = $this->getMethodParams($constructor);

            $this->cache->store($className, $reflectionParameters);
        }

        /** Merge the parameters with the already provided arguments. */
        $mergedParameters = $this->mergeParams($reflectionParameters, $givenArguments);

        /** Load the arguments. Please note: this may be recursive. */
        $loadedParameters = $this->loadParameters($mergedParameters);

        /** return a new instance of the requested class with the loaded arguments. */
        return $this->createNewInstance($reflector, $loadedParameters, $className);
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param array|null $params
     * @param string $className
     *
     * @return object
     */
    protected function createNewInstance(\ReflectionClass $reflectionClass, array $params = null, string $className)
    {
        /** Remove the class from the array of classes being processed. */
        unset($this->processingClasses[$className]);

        /** If the class should be loaded with parameters, do so. */
        if (!empty($params)) {
            return $reflectionClass->newInstanceArgs($params);
        }

        /** Load the class without parameters. */
        return $reflectionClass->newInstance();
    }

    /**
     * Get the ReflectionClass instance for the given class name.
     *
     * @param string $className
     *
     * @return \ReflectionClass
     */
    protected function getReflector(string $className) : \ReflectionClass
    {
        return new \ReflectionClass($className);
    }

    /**
     * @param \ReflectionClass $reflector
     *
     * @return \ReflectionMethod|null
     */
    protected function getConstructor(\ReflectionClass $reflector)
    {
        return $reflector->getConstructor();
    }

    /**
     * @param \ReflectionMethod $reflectionMethod
     * @return string[]
     */
    protected function getMethodParams(\ReflectionMethod $reflectionMethod) : array
    {
        $parameters = $reflectionMethod->getParameters();

        $processedParameters = [];
        foreach ($parameters as $parameter) {
            $processedParameters[$parameter->getName()] = $this->getParameter($parameter);
        }

        return $processedParameters;
    }

    /**
     * @param \ReflectionParameter[]|string[] $reflectionParameters
     * @param mixed[]                         $givenArguments
     *
     * @return mixed[]
     *
     * @throws Exception
     */
    protected function mergeParams(array $reflectionParameters, array $givenArguments) : array
    {
        /** Create an array of parameters. */
        $mergedParams = [];
        /** Start by looping through all ReflectionParameters. */
        foreach ($reflectionParameters as $name => $reflectionParameter) {

            /**
             * If an argument is given with the same name as the ReflectionParameter's name. Add the argument to the
             * array instead.
             */
            if (isset($givenArguments[$name])) {
                $mergedParams[$name] = $givenArguments[$name];
                continue;
            }

            $mergedParams[$name] = $reflectionParameter;
        }

        return $mergedParams;
    }

    /**
     * @param \ReflectionParameter $reflectionParameter
     *
     * @return mixed
     * @throws Exception
     */
    protected function getParameter(\ReflectionParameter $reflectionParameter)
    {
        /** Get the parameter's name. */
        $name = $reflectionParameter->getName();

        /** Get the ReflectionType for the given parameter. */
        $type = $reflectionParameter->getType();

        /** If the type is an auto-loadable class, add it to the array. */
        if (class_exists((string) $type)) {
            /** Process any rewrites that may have been set in the config. */
            return (string) $this->rewrites->processRewrites($type);
        }

        /** If this parameter has a default value specified in the DI config, add that to the array */
        $defaultDiValue = $this->defaultValues->getDefaultDiValue($reflectionParameter);
        if ($defaultDiValue) {
            return $defaultDiValue;
        }

        /** Otherwise, if it has a default value in the function definition, add gthat to the array. */
        if ($reflectionParameter->isDefaultValueAvailable()) {
            return $reflectionParameter->getDefaultValue();
        }

        /**
         * N.B. If the ReflectionParameter is not an auto-loadable class and has no default value, it will not be
         * added to the array. What this means is, that unless the calling function has supplied it in the
         * givenArguments array, it will lead to an error later on when the class is instantiated with insufficient
         * parameters.
         */
        throw new Exception(
            "Parameter {$name} cannot be autoloaded, has no default value and was not given as an argument."
        );
    }

    /**
     * @param mixed[] $mergedParameters
     *
     * @return object[]
     *
     * @throws Exception
     */
    protected function loadParameters(array $mergedParameters) : array
    {
        /** Loop through all parameters and load them if possible. */
        foreach ($mergedParameters as $name => $parameter) {
            /**
             * If the parameter can be autoloaded, do so using this class' getter. This way we can recursively inject
             * dependencies.
             */
            if ($parameter instanceof \ReflectionType
                || (is_string($parameter)
                    && class_exists($parameter)
                )
            ) {
                $mergedParameters[$name] = $this->get($parameter);
            }
        }

        return $mergedParameters;
    }
}
