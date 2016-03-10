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
        $class = $container->create("\\Di\\Tests\\TestClasses\\TestA");

        $this->assertTrue($class instanceof TestA);
    }

    public function testCreateClassWithConstructor()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;
        $class = $container->create("\\Di\\Tests\\TestClasses\\TestB");

        $this->assertTrue($class instanceof TestB);
        $this->assertTrue($class->a instanceof TestA);
    }

    public function testCreateClassWithConstructorAndGivenArgument()
    {
        /** @var \Di\Container $container */
        $container = new $this->className;

        $testC = $container->create("\\Di\\Tests\\TestClasses\\TestC");
        $class = $container->create("\\Di\\Tests\\TestClasses\\TestB", ['a' => $testC]);

        $this->assertTrue($testC instanceof TestC);
        $this->assertTrue($testC instanceof TestA);
        $this->assertTrue($class instanceof TestClasses\TestB);
        $this->assertTrue($class->a instanceof TestC);
        $this->assertEquals($class->a, $testC);
    }
}
