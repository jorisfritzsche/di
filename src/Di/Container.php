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
     * A placeholder value used to identify undefined values. These need to be replaced before creating the class
     * instance or they will lead to an exception.
     */
    const UNDEFINED_PARAMETER = "__undefined__";

    /** Class creation flags. */
    const FLAG_SINGLETON        = 1;
    const FLAG_NO_REWRITE       = 2;
    const FLAG_NO_DEFAULT_VALUE = 4;
    const FLAG_NO_CACHE         = 8;

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
    protected $classOptions = [];

    /**
     * @var array
     */
    protected $classRepository = [];

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
            $rewrites = $this->create("Di\\Config\\Rewrites", [], self::FLAG_SINGLETON);
        }

        if (!$defaultValues) {
            $defaultValues = $this->create("Di\\Config\\DefaultValues", [], self::FLAG_SINGLETON);
        }

        if (!$cache) {
            $cache = $this->create("Di\\Cache\\Classes", [], self::FLAG_SINGLETON);
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
     * @param int    $options
     *
     * @return object
     * @throws Exception
     */
    public function create(string $className, array $givenArguments = [], int $options = 0)
    {
        /** Check if the requested class can be autoloaded. */
        if (!class_exists($className)) {
            throw new Exception("Class {$className} does not exist.");
        }

        /**
         * Make sure this class is not already being processed, in order to prevent infinite recursion. We can easily
         * do this by checking if any options are defined for this class.
         */
        if (isset($this->classOptions[$className])) {
            throw new Exception(
                "Class {$className} is already being processed. This probably means that one or more classes require "
                . "each other, causing an infinite loop."
            );
        }

        /** If rewrites are available, check if there are rewrites configured for the requested class. */
        if ($this->rewrites && !($options & self::FLAG_NO_REWRITE)) {
            $className = $this->rewrites->processRewrites($className);
        }

        /**
         * If the 'singleton' option is set and this class has been stored before as a singleton, return that instance.
         */
        if ($options & self::FLAG_SINGLETON && isset($this->classRepository[$className])) {
            return $this->classRepository[$className];
        }

        /**
         * Add the requested class name to the array of classes being processed along with any options that have been
         * defined. This allows us to recall the options regardless of any recursion.
         */
        $this->classOptions[$className] = $options;

        /** Build the class. */
        return $this->build($givenArguments, $className);
    }

    /**
     * Create the requested class.
     *
     * @param array  $givenArguments
     * @param string $className
     *
     * @return object
     * @throws Exception
     */
    protected function build(array $givenArguments, string $className)
    {
        /** Get the ReflectionClass instance for the requested class name. */
        $reflector = $this->getReflector($className);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class {$className} is not instantiable.");
        }

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
        $reflectionParameters = $this->retrieveClassData($className);
        $skipCache = false;
        if ($reflectionParameters) {
            $skipCache = true;
        } else {
            /** Get the parameters of the constructor. */
            $reflectionParameters = $this->getReflectedParameters($constructor);
        }

        /** Process the parameters to look for default values and to process rewrites. */
        $processedParameters = $this->processParameters($reflectionParameters, $className);

        /** Store the data in the cache. */
        if ($skipCache === false) {
            $this->storeClassData($processedParameters, $className);
        }

        /** Merge the parameters with the already provided arguments. */
        $mergedParameters = $this->mergeParams($processedParameters, $givenArguments);

        /** Load the arguments. Please note: this may be recursive. */
        $loadedParameters = $this->loadParameters($mergedParameters);

        /** return a new instance of the requested class with the loaded arguments. */
        return $this->createNewInstance($reflector, $className, $loadedParameters);
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
     * Get the ReflectionMethod for the ReflectionClass' constructor.
     *
     * @param \ReflectionClass $reflector
     *
     * @return \ReflectionMethod|null
     */
    protected function getConstructor(\ReflectionClass $reflector)
    {
        return $reflector->getConstructor();
    }

    /**
     * Get the parameters from the ReflectionMethod.
     *
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return array
     *
     * @throws Exception
     */
    protected function getReflectedParameters(\ReflectionMethod $reflectionMethod) : array
    {
        /** Get the mthod's parameters. */
        $parameters = $reflectionMethod->getParameters();

        $reflectedParameters = [];
        foreach ($parameters as $parameter) {
            /** Get an array with data about the parameter. This data will be used to process the parameter later on. */
            $reflectedParameters[$parameter->getName()] = $this->getDiReflectionParameter($parameter);
        }

        return $reflectedParameters;
    }

    /**
     * @param \ReflectionParameter $reflectionParameter
     *
     * @return mixed
     * @throws Exception
     */
    protected function getDiReflectionParameter(\ReflectionParameter $reflectionParameter)
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
            'type'        => self::UNDEFINED_PARAMETER,
            'is_variadic' => $isVariadic
        ];
    }

    /**
     * Retrieve class data from the cache.
     *
     * @param string $className
     *
     * @return bool|mixed|null
     */
    protected function retrieveClassData(string $className)
    {
        if (!$this->cache || ($this->classOptions[$className] & self::FLAG_NO_CACHE)) {
            return false;
        }

        $data = $this->cache->retrieve(ltrim($className, '\\'));

        return $data;
    }

    /**
     * Store class data in the cache.
     *
     * @param array  $reflectionParameters
     * @param string $className
     *
     * @return Container
     * @todo fix caching for variadic parameters
     */
    protected function storeClassData(array $reflectionParameters, string $className) : self
    {
        /** If the 'no cache'flag is set for this class, do not store any cache data. */
        if ($this->classOptions[$className] & self::FLAG_NO_CACHE) {
            return $this;
        }

        $this->cache->store(ltrim($className, '\\'), $reflectionParameters);

        return $this;
    }

    /**
     * Process parameters to add rewrites and default values where needed.
     *
     * @param array  $reflectionParameters
     * @param string $className
     *
     * @return array
     */
    protected function processParameters(array $reflectionParameters, string $className) : array
    {
        /** Loop through all parameters and process them if needed. */
        foreach ($reflectionParameters as $name => $parameter) {
            /** Unprocessed scalar values, still need to be processed in order to look for possible default values. */
            if ($parameter == self::UNDEFINED_PARAMETER) {
                $reflectionParameters[$name] = $this->getProcessedParameter(
                    $name,
                    [
                        'type' => self::UNDEFINED_PARAMETER,
                        'parameter' => false
                    ],
                    $className
                );
                continue;
            }

            if (!is_array($parameter) || !isset($parameter['parameter'])) {
                $reflectionParameters[$name] = $parameter;
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
                $reflectionParameters[$name] = $this->getProcessedParameter($name, $parameter, $className);
            }
        }

        return $reflectionParameters;
    }

    /**
     * Get the final parameter for the specified parameter array. This method will attempt to rewrite classes and get
     * default values where applicable.
     *
     * @param string $name
     * @param array  $parameter
     * @param string $className
     *
     * @return mixed
     */
    protected function getProcessedParameter(string $name, array $parameter, string $className)
    {
        /** Get the ReflectionType for the given parameter. */
        $type = $parameter['type'];
        /** @var \ReflectionParameter|false $reflectionParameter */
        $reflectionParameter = $parameter['parameter'];

        /** If the type is an auto-loadable class, add it to the array. */
        if ($this->rewrites
            && class_exists((string) $type)
            && !($this->classOptions[$className] & self::FLAG_NO_REWRITE)
        ) {
            /** Process any rewrites that may have been set in the config. */
            return $this->rewrites->processRewrites($type);
        }

        if ($this->defaultValues && !($this->classOptions[$className] & self::FLAG_NO_DEFAULT_VALUE)) {
            /** If this parameter has a default value specified in the DI config, add that to the array */
            $defaultDiValue = $this->defaultValues->getDefaultDiValue($className, $name);
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

        /**  */
        return self::UNDEFINED_PARAMETER;
    }

    /**
     * Merge the generated parameters with the user-supplied parameters.
     *
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

            /** If no argument was given for this parameter, add the processed parameter to the array. */
            $mergedParams[$name] = $reflectionParameter;
        }

        return $mergedParams;
    }

    /**
     * Load the specified parameters. Any scalar parameters are ignored. Any undefined parameters that can still not be
     * processed, will trigger an exception.
     *
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

            /** Any parameters that are still labeled as "__undefined__" could not be processed. */
            if ($parameter == self::UNDEFINED_PARAMETER) {
                throw new Exception(
                    "Parameter {$name} cannot be autoloaded, has no default value and was not given" .
                    " as an argument."
                );
            }
        }

        return $mergedParameters;
    }

    /**
     * Create a new instance of the specified class. This ends the build-process.
     *
     * @param \ReflectionClass $reflectionClass
     * @param string $className
     * @param array|null $params
     *
     * @return object
     */
    protected function createNewInstance(\ReflectionClass $reflectionClass, string $className, array $params = null)
    {
        /** If the class should be loaded with parameters, do so. */
        if (!empty($params)) {
            $instance = $reflectionClass->newInstanceArgs($params);
        } else {
            /** Load the class without parameters. */
            $instance = $reflectionClass->newInstance();
        }

        /** If the 'singleton' option is set for this class, add it to the class repository for future access. */
        if ($this->classOptions[$className] & self::FLAG_SINGLETON) {
            $this->classRepository[$className] = $instance;
        }

        /** Remove the class from the array of classes being processed. */
        unset($this->classOptions[$className]);

        /** Return the instance. */
        return $instance;
    }
}
