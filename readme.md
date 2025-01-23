[![Build Status](https://app.travis-ci.com/Neuron-PHP/mvc.svg?token=F8zCwpT7x7Res7J2N4vF&branch=master)](https://app.travis-ci.com/Neuron-PHP/mvc)
# Neuron-PHP DTO

## Installation

Install php composer from https://getcomposer.org/

Install the neuron MVC component:

    composer require neuron-php/dto

## Overview

Create DTOs (Data Transfer Objects) for your application.
Load them from a yaml file, or create them manually.
Validation.
Mapping to and from arrays and JSON.

```php
use Neuron\Data\DTO;

$Dto = new Dto();
$Dto->createFromFile( 'test.yaml' );

$Dto->name = 'John Doe';
$Dto->age = 25;


```
# More Information

You can read more about the Neuron components at [neuronphp.com](http://neuronphp.com)
