<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */
declare(strict_types=1);

class JsonTest extends \PHPUnit_Framework_TestCase
{
    protected $className = 'Di\FileLoader\Json';
    protected $containerClassName = 'Di\Container';

    public function testClassExists()
    {
        $classExists = class_exists($this->className);
        $this->assertTrue($classExists);
    }

    public function testSetDirectoryMethodExists()
    {
        /** @var \Di\Container $container */
        $container = new $this->containerClassName();
        /** @var Di\FileLoader\Json $fileLoader */
        $fileLoader = $container->create($this->className);

        $methodExists = method_exists($fileLoader, 'setDirectory');
        $this->assertTrue($methodExists);
    }

    public function testSetDirectoryMethodIsCallable()
    {
        $isCallable = is_callable([$this->className, 'setDirectory']);
        $this->assertTrue($isCallable);
    }

    public function testLoadMethodExists()
    {
        /** @var \Di\Container $container */
        $container = new $this->containerClassName();
        /** @var Di\FileLoader\Json $fileLoader */
        $fileLoader = $container->create($this->className);

        $methodExists = method_exists($fileLoader, 'load');
        $this->assertTrue($methodExists);
    }

    public function testLoadIsCallable()
    {
        $isCallable = is_callable([$this->className, 'load']);
        $this->assertTrue($isCallable);
    }

    public function testSavedMethodExists()
    {
        /** @var \Di\Container $container */
        $container = new $this->containerClassName();
        /** @var Di\FileLoader\Json $fileLoader */
        $fileLoader = $container->create($this->className);

        $methodExists = method_exists($fileLoader, 'save');
        $this->assertTrue($methodExists);
    }

    public function testSaveIsCallable()
    {
        $isCallable = is_callable([$this->className, 'save']);
        $this->assertTrue($isCallable);
    }

    public function testUnreadableFile()
    {
        /** @var \Di\Container $container */
        $container = new $this->containerClassName();
        /** @var Di\FileLoader\Json $fileLoader */
        $fileLoader = $container->create($this->className);

        $this->expectException('\\Di\\Exception');
        $this->expectExceptionMessage('File unreadableFile is not readable.');

        $fileLoader->load('unreadableFile');
    }

    public function testNonJsonFile()
    {
        /** @var \Di\Container $container */
        $container = new $this->containerClassName();
        /** @var Di\FileLoader\Json $fileLoader */
        $fileLoader = $container->create($this->className);

        $this->expectException('\\Di\\Exception');
        $this->expectExceptionMessage('File ../src/Di/Tests/FileLoader/files/not_json.txt is not a JSON file.');

        $fileLoader->load('../src/Di/Tests/FileLoader/files/not_json.txt');
    }

    public function testEmptyFile()
    {
        /** @var \Di\Container $container */
        $container = new $this->containerClassName();
        /** @var Di\FileLoader\Json $fileLoader */
        $fileLoader = $container->create($this->className);

        $this->expectException('\\Di\\Exception');
        $this->expectExceptionMessage('File ../src/Di/Tests/FileLoader/files/empty.json is empty.');

        $fileLoader->load('../src/Di/Tests/FileLoader/files/empty.json');
    }

    public function testInvalidData()
    {
        /** @var \Di\Container $container */
        $container = new $this->containerClassName();
        /** @var Di\FileLoader\Json $fileLoader */
        $fileLoader = $container->create($this->className);

        $this->expectException('\\Di\\Exception');
        $this->expectExceptionMessage(
            'File ../src/Di/Tests/FileLoader/files/not_json_data.json does not contain valid JSON.'
        );

        $fileLoader->load('../src/Di/Tests/FileLoader/files/not_json_data.json');
    }

    public function testSaveValidData()
    {
        /** @var \Di\Container $container */
        $container = new $this->containerClassName();
        /** @var Di\FileLoader\Json $fileLoader */
        $fileLoader = $container->create($this->className);

        $fp = fopen('src/Di/Tests/FileLoader/files/valid_save.json', 'w+');
        fclose($fp);

        $fileLoader->save('../src/Di/Tests/FileLoader/files/valid_save.json', '{}');
        $savedData = file_get_contents('src/Di/Tests/FileLoader/files/valid_save.json');

        $this->assertSame($savedData, '{}');
    }

    public function testSaveDataToNonWritableFile()
    {
        /** @var \Di\Container $container */
        $container = new $this->containerClassName();
        /** @var Di\FileLoader\Json $fileLoader */
        $fileLoader = $container->create($this->className);

        $this->expectException('\\Di\\Exception');
        $this->expectExceptionMessage('File src/Di/Tests/FileLoader/files/non_existing_file.json is not readable.');

        $fileLoader->save('src/Di/Tests/FileLoader/files/non_existing_file.json', '{}');
    }
}
