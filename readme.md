Develop: [![Build Status](https://app.travis-ci.com/Neuron-PHP/dto.svg?token=F8zCwpT7x7Res7J2N4vF&branch=develop)](https://app.travis-ci.com/Neuron-PHP/dto)

Master: [![Build Status](https://app.travis-ci.com/Neuron-PHP/dto.svg?token=F8zCwpT7x7Res7J2N4vF&branch=master)](https://app.travis-ci.com/Neuron-PHP/dto)

# Neuron-PHP DTO

## Installation

Install php composer from https://getcomposer.org/

Install the neuron MVC component:

    composer require neuron-php/dto

## Overview

* Create and validate DTOs (Data Transfer Objects) for your application.
* Load them from a yaml file, or create them manually.
* Define and ingest external data.

### DTOs
Example configuration:

test.yaml

```yaml
dto:
  username:
    type: string
    length:
      min: 3
      max: 20
    required: true
  password:
    required: true
    type: string
    length:
      min: 8
      max: 10
  age:
    type: integer
    range:
      min: 18
      max: 40
  birthdate:
    type: string
    format: date
    pattern: '/^\d{4}-\d{2}-\d{2}$/'  # YYYY-MM-DD format
  inventory:  
    type: array
    items:
      type: object
      properties:
        name:
          required: true
          type: string
        amount:
          required: true
          type: integer
        attributes:
          type: array
          items:
            type: string
  address:
    required: true
    type: object
    properties:
      street:
        required: true
        type: string
        length:
          min: 3
          max: 10
      city:
        required: true
        type: string
        length:
            min: 3
            max: 20
      state:
        required: true
        type: string
        length:
            min: 2
            max: 2
      zip:
        required: true
        type: string
        length:
            min: 3
            max: 20
```

Create a DTO.

DTOs support hierarchical/nested parameters.
```php
$DtoFactory = new DtoFactory( 'examples/test.yaml' );
$Dto = $DtoFactory->create();

$Dto->username = 'Fred';
$Dto->address->street = '13 Mockingbird lane.'
```

#### Validation

```php
$Dto->validate()

print_r( $Dto->getErrors() );
```

This will display all validation errors for all parameters.

You can also access validation errors for an individual parameter.
```php
print_r( $Dto->getParameter( 'username' )->getErrors() );
```

#### JSON
To output the contents of the DTO as JSON, use the getAsJson() method.

```php
echo $Dto->getAsJson();
```

### Mappers

Mappers allow the dynamic mapping of different data structures.

test-json-map.yaml
```yaml
map:
  test.username: user.name
  test.password: user.password
  test.age: user.age
  
  test.address.street: user.address.street
  test.address.city: user.address.city
  test.address.state: user.address.state
  test.address.zip: user.address.zip

  test.inventory: user.inventory
  test.inventory.name: user.inventory.name
  test.inventory.amount: user.inventory.count
  test.inventory.attributes: user.inventory.attributes
```

Create a mapper, ingest and map external data.

```php
$MapperFactory = new \Neuron\Dto\MapperFactory( 'examples/test-json-map.yaml' );
$Mapper = $MapperFactory->create();

$Payload = [
    'user' => [
        'name' => 'test',
        'password' => 'testtest',
        'age'      => 40,
        'birthday' => '1978-01-01',
        'address'  => [
            'street' => '13 Mocking',
            'city'   => 'Mockingbird Heights',
            'state'  => 'CA',
            'zip'    => '90210'
        ],
        'inventory' => [
            [
                'name' => 'shoes',
                'count' => 1,
                'attributes' => [
                    [
                        'name' => 'leather',
                    ],
                    [
                        'name' => 'boot',
                    ],
                    [
                        'name' =>'smelly'
                    ]
                ]
            ],
            [
                'name' => 'jackets',
                'count' => 2
            ],
            [
                'name' => 'pants',
                'count' => 3
            ]
        ]
    ]
];

$Mapper->map( $Dto, $Payload );

echo $Dto->username; // outputs 'test'
echo $Dto->inventory[ 1 ]->amount; // outputs 3
echo $Dto->inventory[ 0 ]->attributes[ 1 ]; // outputs 'boot'

```

# More Information

You can read more about the Neuron components at [neuronphp.com](http://neuronphp.com)
