<?php

namespace Di;

class Factory
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * Factory constructor.
     */
    public function __construct()
    {
        $config = new Config();
        $config->init();

        $this->config = $config;
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

        /** Get the ReflectionClass instance for the requested class name. */
        $reflector = $this->getReflector($className);
        /** Get the ReflectionArgument instance for the class' constructor, if it has one. */
        $constructor = $this->getConstructor($reflector);

        /** if no constructor is defined, simply return a new instance of the class. */
        if (!$constructor) {
            return $reflector->newInstance();
        }

        /** If the constructor has not parameters, simply return a new instance of the class. */
        if ($constructor->getNumberOfParameters() == 0) {
            return $reflector->newInstance();
        }

        /** Get the paremeters of the constructor. */
        $reflectionParameters = $this->getMethodParams($constructor);
        /** Merge the parameters with the already provided arguments. */
        $mergedParameters = $this->mergeParams($reflectionParameters, $givenArguments);
        /** Load the arguments. Please note: this may be recursive. */
        $loadedParameters = $this->loadParameters($mergedParameters);

        /** return a new instance of the requested class with the loaded arguments. */
        return $reflector->newInstanceArgs($loadedParameters);
    }

    /**
     * Get the ReflectionClass instance for the given class name.
     *
     * @param string $className
     *
     * @return \ReflectionClass
     */
    protected function getReflector(string $className)
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
     * @return \ReflectionParameter[]
     */
    protected function getMethodParams(\ReflectionMethod $reflectionMethod)
    {
        $parameters = $reflectionMethod->getParameters();

        return $parameters;
    }

    /**
     * @param \ReflectionParameter[] $reflectionParameters
     * @param mixed[]                $givenArguments
     *
     * @return \mixed[]
     *
     * @throws Exception
     */
    protected function mergeParams(array $reflectionParameters, array $givenArguments)
    {
        /** Create an array of parameters. */
        $mergedParams = [];
        /** Start by looping through all ReflectionParameters. */
        foreach ($reflectionParameters as $reflectionParameter) {
            /**
             * If an argument is given with the same name as the ReflectionParameter's name. Add the argument to the
             * array instead.
             */
            if (isset($givenArguments[$reflectionParameter->getName()])) {
                $mergedParams[$reflectionParameter->getName()] = $givenArguments[$reflectionParameter->getName()];
                continue;
            }

            $mergedParams[$reflectionParameter->getName()] = $this->getParameter($reflectionParameter);
        }

        return $mergedParams;
    }

    /**
     * @param \ReflectionParameter $reflectionParameter
     *
     * @return false|mixed|\ReflectionType|string
     * @throws Exception
     */
    protected function getParameter(\ReflectionParameter $reflectionParameter)
    {
        /** Get the parameter's name. */
        $name = $reflectionParameter->getName();

        /** Get the ReflectionType for the given parameter. */
        $type = $reflectionParameter->getType();

        /** If the type is an auto-loadable class, add it to the array. */
        if (class_exists($type)) {
            /** Process any rewrites that may have been set in the config. */
            return $this->processRewrites($type);
        }

        /** If this parameter has a default value specified in the DI config, add that to the array */
        $defaultDiValue = $this->getDefaultDiValue($reflectionParameter);
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
     * Process any rewrites found in the DI config.
     *
     * @param \ReflectionType $type
     *
     * @return \ReflectionType|string
     */
    protected function processRewrites(\ReflectionType $type)
    {
        /** Get the type's class name and make sure it starts in the root namespace. */
        $stringType = (string) $type;
        if (strpos($stringType, '\\') !== 0) {
            $stringType = '\\' . $stringType;
        }

        /** Check for any rewrites and return the rewritten class name if available. */
        if (isset($this->config->rewrites[$stringType])) {
            $type = $this->config->rewrites[$stringType];
        }

        return $type;
    }

    /**
     * Try to find a default value for this parameter from the DI config.
     *
     * @param \ReflectionParameter $reflectionParameter
     *
     * @return mixed|false
     */
    protected function getDefaultDiValue(\ReflectionParameter $reflectionParameter)
    {
        /** Get the parameter's declaring class' class name and make sure it starts in the root namespace. */
        $className = $reflectionParameter->getDeclaringClass()->getName();
        if (strpos($className, '\\') !== 0) {
            $className = '\\' . $className;
        }

        /** Check if a default value is defined for this parameter and return it. */
        $defaultValues = $this->config->defaultValues;
        if (isset($defaultValues[$className][$reflectionParameter->getName()])) {
            return $defaultValues[$className][$reflectionParameter->getName()];
        }

        return false;
    }

    /**
     * @param mixed[] $mergedParameters
     *
     * @return \object[]
     *
     * @throws Exception
     */
    protected function loadParameters(array $mergedParameters)
    {
        /** Loop through all parameters and load them if possible. */
        foreach ($mergedParameters as $name => $parameter) {
            /**
             * If the parameter can be autoloaded, do so using this class' getter. This way we can recursively inject
             * dependencies.
             */
            if ($parameter instanceof \ReflectionType || class_exists($parameter)) {
                $mergedParameters[$name] = $this->get($parameter);
            }
        }

        return $mergedParameters;
    }
}
