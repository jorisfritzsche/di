# DI - a dependency injector experiment for PHP
DI allows you to load classes with dependency injection. DI will automatically check each class' requirements and add them as needed. This works for classes which require other classes during construction, as well as for scalar values with or without default values.

DI allows for class rewriting as well as pre-defined scalar default values.

**As the name might suggest; this is a personal experiment. It is not recommended to use this for anything important.**

## Installation
Install the latest version with

```bash

$ composer require jorisfritzsche/di
```

## Basic usage

```php
<?php
$factory = new \Di\Factory();

$test = $factory->get('\SomeNameSpace\SomeClass');

```

## CLI usage
DI comes shipped with a CLI that allows you to modify some of it's behaviour.

```bash
bin/di --version
bin/di --help
bin/di --add-rewrite {\"\\\\SomeNameSpace\\\\SomeClass\":\"\\\\SomeNameSpace\\\\SomeOtherClass\"}
bin/di --add-default-value {\"\\\\SomeNameSpace\\\\SomeClass\":{\"someParameterName\":\"some_default_value\"}}
bin/di --set-env production/develop
bin/di --clear-cache
bin/di --clear-config=some_config

```


# About

## Requirements
* DI requires PHP 7.0 or above.

## Author
Joris Fritzsche - joris.fritzsche@outlook.com

# TODO
* Debugging support through monolog or other loggers
* Full support for environment variables
* Refactor CLI class
* Think of a decent name for this project
* Unittests
* Support for variadic parameters
* Additional caching for various config values
* Read config values through CLI