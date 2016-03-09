<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */

declare(strict_types = 1);

namespace Di;

/**
 * @todo Currently there are two ways parameters are passed throught his class: as an associative array or as a discrete
 *       value. These two methods should be standardized.
 */
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
     * Container constructor.
     *
     * @param Config\Rewrites      $rewrites
     * @param Config\DefaultValues $defaultValues
     * @param Cache\Classes        $cache
     */
    public function __construct(
        Config\Rewrites $rewrites = null,
        Config\DefaultValues $defaultValues = null,
        Cache\Classes $cache = null
    ) {
        /** Load required classes if they have not been passed as parameters. */
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
            return $this->createNewInstance($reflector, $className);
        }

        /** If the constructor has not parameters, simply return a new instance of the class. */
        if ($constructor->getNumberOfParameters() == 0) {
            return $this->createNewInstance($reflector, $className);
        }

        /** Get the parameters from the cache, if available. */
        $reflectionParameters = $this->retrieveClassData();
        $skipCache = false;
        if ($reflectionParameters) {
            $skipCache = true;
        } else {
            /** Get the parameters of the constructor. */
            $reflectionParameters = $this->getMethodParams($constructor);
        }

        /** Process the parameters to look for default values and to process rewrites. */
        $processedParameters = $this->processParameters($reflectionParameters);

        /** Store the data in the cache. */
        if ($skipCache === false) {
            $this->storeClassData($processedParameters);
        }

        /** Merge the parameters with the already provided arguments. */
        $mergedParameters = $this->mergeParams($processedParameters, $givenArguments);

        /** Load the arguments. Please note: this may be recursive. */
        $loadedParameters = $this->loadParameters($mergedParameters);

        /** return a new instance of the requested class with the loaded arguments. */
        return $this->createNewInstance($reflector, $className, $loadedParameters);
    }

    /**
     * Retrieve data from the cache.
     *
     * @return bool|mixed|null
     */
    protected function retrieveClassData()
    {
        if (!$this->cache) {
            return false;
        }

        $data = $this->cache->retrieve(ltrim($this->className, '\\'));

        return $data;
    }

    /**
     * Prepare the found class data for storage.
     *
     * @param array  $reflectionParameters
     *
     * @return Container
     */
    protected function storeClassData(array $reflectionParameters) : self
    {
        $this->cache->store(ltrim($this->className, '\\'), $reflectionParameters);

        return $this;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param string $className
     * @param array|null $params
     *
     * @return object
     */
    protected function createNewInstance(\ReflectionClass $reflectionClass, string $className, array $params = null)
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
                /**
                 * If the parameter is variadic, only replace the 'parameter' key with the given argument. We will need
                 * the other data later on.
                 */
                if (!empty($reflectionParameter['is_variadic'])) {
                    $reflectionParameter['parameter'] = $givenArguments[$name];

                    $mergedParams[$name] = $reflectionParameter;
                    continue;
                }

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

        /** Check if the parameter is variadic (i.e. it accepts multiple arguments). */
        $isVariadic = $reflectionParameter->isVariadic();

        /** If the type is an auto-loadable class, add it to the array. */
        if ($this->rewrites && class_exists((string) $type)) {
            /** Process any rewrites that may have been set in the config. */
            return [
                'parameter'   => $reflectionParameter,
                'type'        => (string) $this->rewrites->processRewrites((string) $type),
                'is_variadic' => $isVariadic
            ];
        } elseif (class_exists((string) $type)) {
            return [
                'parameter'   => $reflectionParameter,
                'type'        => (string) $type,
                'is_variadic' => $isVariadic
            ];
        }

        return [
            'parameter'   => $reflectionParameter,
            'type'        => '__scalar__',
            'is_variadic' => $isVariadic
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
            return $this->rewrites->processRewrites($type);
        }

        if ($this->defaultValues) {
            /** If this parameter has a default value specified in the DI config, add that to the array */
            $defaultDiValue = $this->defaultValues->getDefaultDiValue($this->className, $name);
            if ($defaultDiValue) {
                return $defaultDiValue;
            }
        }

        /** Otherwise, if it has a default value in the function definition, add that to the array. */
        if ($reflectionParameter instanceof \ReflectionParameter
            && $reflectionParameter->isDefaultValueAvailable()
        ) {
            return $reflectionParameter->getDefaultValue();
        }

        return "__scalar__";
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

            if (!is_array($parameter) || !isset($parameter['parameter'])) {
                $mergedParameters[$name] = $parameter;
                continue;
            }

            /**
             * Some parameters still need to be processed before autoloading. These are stored as an array with 2 items:
             *  "parameter": \ReflectionParameter
             *  "type": string
             *
             *  @todo move this logic to a separate method or class.
             */

            /** Process unresolved ReflectionParameters. */
            if ($parameter['parameter'] instanceof \ReflectionParameter) {
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

            if ($parameter == "__scalar__") {
                throw new Exception(
                    "Parameter {$name} cannot be autoloaded, has no default value and was not given" .
                    " as an argument."
                );
            }
        }

        return $mergedParameters;
    }
}
