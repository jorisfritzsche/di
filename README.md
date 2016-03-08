# DI - a dependency injector experiment for PHP
DI allows you to load classes with dependency injection. DI will automatically check each class' requirements and add them as needed. This works for classes which require other classes during construction, as well as for scalar values with or without default values.

DI allows for class rewriting as well as pre-defined scalar default values.

## Installation
Install the latest version with

```bash

$ composer require tig-jorisfritzsche/di
```

## Basic usage

```php
<?php
$factory = new \Di\Factory();

$test = $factory->get('\SomeNameSpace\SomeClass');

```

# About

## Requirements
* DI requires PHP 7.0 or above.

## Author
Joris Fritzsche - joris.fritzsche@outlook.com