<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */

declare(strict_types = 1);

namespace Di\Tests;

use Di\Tests\TestClasses\TestA;
use Di\Tests\TestClasses\TestB;
use Di\Tests\TestClasses\TestC;
use Di\Tests\TestClasses\TestD;

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
    protected $className = 'Di\Container';

    public function testClassExists()
    {
        $classExists = class_exists($this->className);
        $this->assertTrue($classExists);
    }

    public function testCreateMethodExists()
    {
        $methodExists = method_exists($this->className, 'create');
        $this->assertTrue($methodExists);
    }

    public function testCreateMethodCallable()
    {
        $methodExists = is_callable(array($this->className, 'create'));
        $this->assertTrue($methodExists);
    }

    public function testBuildMethodExists()
    {
        $methodExists = method_exists($this->className, 'build');
        $this->assertTrue($methodExists);
    }

    public function testGetReflectorMethodExists()
    {
        $methodExists = method_exists($this->className, 'getReflector');
        $this->assertTrue($methodExists);
    }

    public function testGetConstructorMethodExists()
    {
        $methodExists = method_exists($this->className, 'getConstructor');
        $this->assertTrue($methodExists);
    }

    public function testGetReflectedParametersMethodExists()
    {
        $methodExists = method_exists($this->className, 'getReflectedParameters');
        $this->assertTrue($methodExists);
    }

    public function testGetDiReflectionParameterMethodExists()
    {
        $methodExists = method_exists($this->className, 'getDiReflectionParameter');
        $this->assertTrue($methodExists);
    }

    public function testRetrieveClassDataMethodExists()
    {
        $methodExists = method_exists($this->className, 'retrieveClassData');
        $this->assertTrue($methodExists);
    }

    public function testStoreClassDataMethodExists()
    {
        $methodExists = method_exists($this->className, 'storeClassData');
        $this->assertTrue($methodExists);
    }

    public function testProcessParametersMethodExists()
    {
        $methodExists = method_exists($this->className, 'processParameters');
        $this->assertTrue($methodExists);
    }

    public function testGetProcessedParameterMethodExists()
    {
        $methodExists = method_exists($this->className, 'getProcessedParameter');
        $this->assertTrue($methodExists);
    }

    public function testMergeParamsMethodExists()
    {
        $methodExists = method_exists($this->className, 'mergeParams');
        $this->assertTrue($methodExists);
    }

    public function testLoadParametersMethodExists()
    {
        $methodExists = method_exists($this->className, 'loadParameters');
        $this->assertTrue($methodExists);
    }

    public function testCreateNewInstanceMethodExists()
    {
        $methodExists = method_exists($this->className, 'createNewInstance');
        $this->assertTrue($methodExists);
    }

    public function testCreateClass()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;
        $class = $container->create("\\Di\\Tests\\TestClasses\\TestA", [], $container::FLAG_NO_CACHE);

        $this->assertTrue($class instanceof TestA);
    }

    public function testCreateClassWithConstructor()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;
        $class = $container->create("\\Di\\Tests\\TestClasses\\TestB", [], $container::FLAG_NO_CACHE);

        $this->assertTrue($class instanceof TestB);
        $this->assertTrue($class->a instanceof TestA);
    }

    public function testCreateClassWithConstructorAndGivenArgument()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;

        $testC = $container->create("\\Di\\Tests\\TestClasses\\TestC", [], $container::FLAG_NO_CACHE);
        $class = $container->create("\\Di\\Tests\\TestClasses\\TestB", ['a' => $testC], $container::FLAG_NO_CACHE);

        $this->assertTrue($testC instanceof TestC);
        $this->assertTrue($testC instanceof TestA);
        $this->assertTrue($class instanceof TestClasses\TestB);
        $this->assertTrue($class->a instanceof TestC);
        $this->assertEquals($class->a, $testC);
    }

    public function testCreateSingletonClass()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;
        $classA = $container->create(
            "\\Di\\Tests\\TestClasses\\TestA",
            [],
            $container::FLAG_SINGLETON | $container::FLAG_NO_CACHE
        );
        $classB = $container->create(
            "\\Di\\Tests\\TestClasses\\TestA",
            [],
            $container::FLAG_SINGLETON | $container::FLAG_NO_CACHE
        );

        $this->assertTrue($classA instanceof TestA);
        $this->assertTrue($classB instanceof TestA);

        $this->assertSame($classA, $classB);
    }

    public function testRewrite()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;
        $container->rewrites->addRewrite(["\\Di\\Tests\\TestClasses\\TestA" => "\\Di\\Tests\\TestClasses\\TestC"]);

        $class = $container->create("\\Di\\Tests\\TestClasses\\TestA", [], $container::FLAG_NO_CACHE);

        $this->assertTrue($class instanceof TestC);
        $this->assertTrue($class instanceof TestA);
    }

    public function testDefaultValue()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;
        $container->defaultValues->addDefaultValue("\\Di\\Tests\\TestClasses\\TestD", ["scalarValue" => "test_value"]);

        $class = $container->create("\\Di\\Tests\\TestClasses\\TestD", [], $container::FLAG_NO_CACHE);

        $this->assertTrue($class instanceof TestD);
        $this->assertSame($class->scalarValue, "test_value");
    }

    public function testMissingValue()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;

        $this->expectException("\\Di\\Exception");

        $container->create("\\Di\\Tests\\TestClasses\\TestD", [], $container::FLAG_NO_CACHE);
    }

    public function testCache()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;

        $container->cache->store("Di\\Tests\\TestClasses\\TestB", ['a' => "\\Di\\Tests\\TestClasses\\TestC"]);

        $class = $container->create("\\Di\\Tests\\TestClasses\\TestB");
        $this->assertTrue($class instanceof TestB);
        $this->assertTrue($class->a instanceof TestC);
        $this->assertTrue($class->a instanceof TestA);
    }

    public function testNoCacheFlag()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;

        $container->cache->store("Di\\Tests\\TestClasses\\TestB", ['a' => "\\Di\\Tests\\TestClasses\\TestC"]);

        $class = $container->create("\\Di\\Tests\\TestClasses\\TestB", [], $container::FLAG_NO_CACHE);
        $this->assertTrue($class instanceof TestB);
        $this->assertFalse($class->a instanceof TestC);
        $this->assertTrue($class->a instanceof TestA);
    }

    public function testNonExistingClass()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;

        $this->expectException("\\Di\\Exception");
        $this->expectExceptionMessage("Class SomeNonExistingClass does not exist.");

        $container->create("SomeNonExistingClass", [], $container::FLAG_NO_CACHE);
    }

    public function testIncludeLoop()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;

        $this->expectException("\\Di\\Exception");
        $this->expectExceptionMessage(
            "Class Di\\Tests\\TestClasses\\TestE is already being processed. This probably means that one or more"
            . " classes require each other, causing an infinite loop."
        );

        $container->create("\\Di\\Tests\\TestClasses\\TestE", [], $container::FLAG_NO_CACHE);
    }

    public function testNonInstantiableClass()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;

        $this->expectException("\\Di\\Exception");
        $this->expectExceptionMessage("Class \\Di\\Tests\\TestClasses\\TestF is not instantiable.");

        $container->create("\\Di\\Tests\\TestClasses\\TestF", [], $container::FLAG_NO_CACHE);
    }

    public function testDefaultParameterValue()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;

        $class = $container->create("\\Di\\Tests\\TestClasses\\TestG", [], $container::FLAG_NO_CACHE);

        $this->assertSame($class->g, "default_value");
    }
}
