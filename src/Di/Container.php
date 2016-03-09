<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */

declare(strict_types = 1);

namespace Di;

class Container
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
     * @var string
     */
    protected $className;

    /**
     * Factory constructor.
     *
     * @param Config\Rewrites      $rewrites
     * @param Config\DefaultValues $defaultValues
     * @param Cache\Classes        $cache
     */
    public function __construct(
        Config\Rewrites $rewrites = null, Config\DefaultValues $defaultValues = null, Cache\Classes $cache = null
    ) {
        if (!$rewrites) {
            $rewrites = $this->create("Di\\Config\\Rewrites");
        }

        if (!$defaultValues) {
            $defaultValues = $this->create("Di\\Config\\DefaultValues");
        }

        if (!$cache) {
            $cache = $this->create("Di\\Cache\\Classes");
        }

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
    public function create(string $className, array $givenArguments = [])
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

        $this->className = $className;

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

        /** Get the parameters from the cache, if available. */
        $reflectionParameters = false;
        if ($this->cache) {
            var_dump($this->cache, $className);
            echo '==============' . PHP_EOL;
            $reflectionParameters = $this->cache->retrieve(ltrim($className, '\\'));
        }

        if (!$reflectionParameters) {
            /** Get the parameters of the constructor. */
            $reflectionParameters = $this->getMethodParams($constructor);

            $this->storeClassData($className, $reflectionParameters);
        }

        /** Merge the parameters with the already provided arguments. */
        $mergedParameters = $this->mergeParams($reflectionParameters, $givenArguments);

        /** Process the paramaters to look for default values and to process rewrites. */
        $processedParameters = $this->processParameters($mergedParameters);

        /** Load the arguments. Please note: this may be recursive. */
        $loadedParameters = $this->loadParameters($processedParameters);

        /** return a new instance of the requested class with the loaded arguments. */
        return $this->createNewInstance($reflector, $loadedParameters, $className);
    }

    /**
     * Prepare the found class data for storage.
     *
     * @param string $className
     * @param array  $reflectionParameters
     *
     * @return Container
     */
    protected function storeClassData(string $className, array $reflectionParameters) : self
    {
        $storableData = [];
        /** Go through all parameters. */
        foreach ($reflectionParameters as $name => $parameter) {
            /**
             * If the parameter is scalar, process it.
             *
             * N.B. Processing the parameters will happen again later on, after the parameters have been merged with the
             * user provided arguments, so this does constitude a hit to performance. However, since the results will be
             * cached, the hit is minimal.
             *
             * @todo refactor so we don't have to do this twice.
             */
            if ($parameter['type'] == "__scalar__") {
                /**
                 * @todo find a way to do this without the try / catch.
                 */
                try {
                    $storableData[$name] = $this->getParameter($name, $parameter);
                    continue;
                } catch (Exception $e) {
                }
            }

            $storableData[$name] = $parameter['type'];
        }

        $this->cache->store(ltrim($className, '\\'), $storableData);

        return $this;
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
     *
     * @return array
     *
     * @throws Exception
     */
    protected function getMethodParams(\ReflectionMethod $reflectionMethod) : array
    {
        $parameters = $reflectionMethod->getParameters();

        $processedParameters = [];
        foreach ($parameters as $parameter) {
            $processedParameters[$parameter->getName()] = $this->getParameterType($parameter);
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
    protected function getParameterType(\ReflectionParameter $reflectionParameter)
    {
        /** Get the ReflectionType for the given parameter. */
        $type = $reflectionParameter->getType();

        /** If the type is an auto-loadable class, add it to the array. */
        if ($this->rewrites && class_exists((string) $type)) {
            /** Process any rewrites that may have been set in the config. */
            return [
                'parameter' => $reflectionParameter,
                'type'      => (string) $this->rewrites->processRewrites((string) $type),
            ];
        } elseif (class_exists((string) $type)) {
            return [
                'parameter' => $reflectionParameter,
                'type'      => (string) $type,
            ];
        }

        return [
            'parameter' => $reflectionParameter,
            'type'      => '__scalar__',
        ];
    }

    /**
     * @param string $name
     * @param array  $parameter
     *
     * @return mixed
     * @throws Exception
     */
    protected function getParameter(string $name, array $parameter)
    {
        /** Get the ReflectionType for the given parameter. */
        $type = $parameter['type'];
        /** @var \ReflectionParameter|false $reflectionParameter */
        $reflectionParameter = $parameter['parameter'];

        /** If the type is an auto-loadable class, add it to the array. */
        if ($this->rewrites && class_exists((string) $type)) {
            /** Process any rewrites that may have been set in the config. */
            return (string) $this->rewrites->processRewrites($type);
        }

        if ($this->defaultValues) {
            /** If this parameter has a default value specified in the DI config, add that to the array */
            $defaultDiValue = $this->defaultValues->getDefaultDiValue($this->className, $name);
            if ($defaultDiValue) {
                return $defaultDiValue;
            }
        }

        /** Otherwise, if it has a default value in the function definition, add that to the array. */
        if ($reflectionParameter && $reflectionParameter->isDefaultValueAvailable()) {
            return $reflectionParameter->getDefaultValue();
        }

        throw new Exception(
            "Parameter {$name} cannot be autoloaded, has no default value and was not given" .
            " as an argument."
        );
    }

    /**
     * @param array $mergedParameters
     *
     * @return array
     * @throws Exception
     */
    protected function processParameters(array $mergedParameters) : array
    {
        /** Loop through all parameters and process them if needed. */
        foreach ($mergedParameters as $name => $parameter) {
            /** unprocessed scalar values, still need to be processed in order to look for possible default values. */
            if ($parameter == "__scalar__") {
                $mergedParameters[$name] = $this->getParameter($name, ['type' => "__scalar__", 'parameter' => false]);
                continue;
            }

            /**
             * Some parameters still need to be processed before autoloading. These are stored as an array with 2 items:
             *  "parameter": \ReflectionParameter
             *  "type": string
             *
             * @todo move this logic to a separate method or class.
             */
            if (is_array($parameter)
                && isset($parameter['parameter'])
                && $parameter['parameter'] instanceof \ReflectionParameter
            ) {
                /**
                 * Process the parameter array. This will process rewrites and default values.
                 */
                $mergedParameters[$name] = $this->getParameter($name, $parameter);
            }
        }

        return $mergedParameters;
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
                $mergedParameters[$name] = $this->create((string) $parameter);
                continue;
            }
        }

        return $mergedParameters;
    }
}
