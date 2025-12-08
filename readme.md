[![CI](https://github.com/Neuron-PHP/dto/actions/workflows/ci.yml/badge.svg)](https://github.com/Neuron-PHP/dto/actions)
[![codecov](https://codecov.io/gh/Neuron-PHP/dto/graph/badge.svg)](https://codecov.io/gh/Neuron-PHP/dto)

# Neuron-PHP DTO

A powerful Data Transfer Object (DTO) library for PHP 8.4+ that provides dynamic DTO creation, comprehensive validation, and flexible data mapping capabilities with support for nested structures and YAML configuration.

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Core Features](#core-features)
- [DTO Configuration](#dto-configuration)
- [Creating DTOs](#creating-dtos)
- [Validation](#validation)
- [Data Mapping](#data-mapping)
- [Property Types](#property-types)
- [Collections](#collections)
- [Advanced Usage](#advanced-usage)
- [Testing](#testing)
- [Best Practices](#best-practices)
- [More Information](#more-information)

## Installation

### Requirements

- PHP 8.4 or higher
- Composer
- symfony/yaml (^6.4)
- neuron-php/validation (^0.7.0)

### Install via Composer

```bash
composer require neuron-php/dto
```

## Quick Start

### 1. Define Your DTO Structure

Create a YAML configuration file (`user.yaml`):

```yaml
dto:
  username:
    type: string
    required: true
    length:
      min: 3
      max: 20
  email:
    type: email
    required: true
  age:
    type: integer
    range:
      min: 18
      max: 120
```

### 2. Create and Use the DTO

```php
use Neuron\Dto\Factory;

// Create DTO from configuration
$factory = new Factory('user.yaml');
$dto = $factory->create();

// Set values
$dto->username = 'johndoe';
$dto->email = 'john@example.com';
$dto->age = 25;

// Validate
if (!$dto->validate()) {
    $errors = $dto->getErrors();
    // Handle validation errors
}

// Export as JSON
echo $dto->getAsJson();
```

## Core Features

- **Dynamic DTO Creation**: Generate DTOs from YAML configuration files
- **Comprehensive Validation**: Built-in validators for 20+ data types
- **Nested Structures**: Support for complex, hierarchical data models
- **Data Mapping**: Transform external data structures to DTOs
- **Type Safety**: Strict type checking and validation
- **Collections**: Handle arrays of objects with validation
- **JSON Export**: Easy serialization to JSON format
- **Custom Validators**: Extend with custom validation logic

## DTO Configuration

### Basic Structure

DTOs are configured using YAML files with property definitions:

```yaml
dto:
  propertyName:
    type: string|integer|boolean|array|object|etc
    required: true|false
    # Additional validation rules
```

### Complete Example

```yaml
dto:
  # Simple string property
  firstName:
    type: string
    required: true
    length:
      min: 2
      max: 50

  # Email with validation
  email:
    type: email
    required: true

  # Integer with range
  age:
    type: integer
    range:
      min: 0
      max: 150

  # Date with pattern
  birthDate:
    type: date
    pattern: '/^\d{4}-\d{2}-\d{2}$/'  # YYYY-MM-DD

  # Nested object
  address:
    type: object
    required: true
    properties:
      street:
        type: string
        required: true
        length:
          min: 5
          max: 100
      city:
        type: string
        required: true
      state:
        type: string
        length:
          min: 2
          max: 2
      zipCode:
        type: string
        pattern: '/^\d{5}(-\d{4})?$/'  # US ZIP code

  # Array of objects
  phoneNumbers:
    type: array
    items:
      type: object
      properties:
        type:
          type: string
          enum: ['home', 'work', 'mobile']
          required: true
        number:
          type: string
          pattern: '/^\+?[\d\s\-\(\)]+$/'
          required: true

  # Array of primitives
  tags:
    type: array
    items:
      type: string
      length:
        min: 1
        max: 20
```

## Creating DTOs

### From YAML Configuration

```php
use Neuron\Dto\Factory;

// Load from file
$factory = new Factory('path/to/neuron.yaml');
$dto = $factory->create();

// Set properties
$dto->firstName = 'John';
$dto->email = 'john@example.com';
$dto->age = 30;
```

### Programmatic Creation

```php
use Neuron\Dto\Dto;
use Neuron\Dto\Property;

$dto = new Dto();

// Create string property
$username = new Property();
$username->setName('username');
$username->setType('string');
$username->setRequired(true);
$username->addLengthValidator(3, 20);

$dto->addProperty($username);

// Create email property
$email = new Property();
$email->setName('email');
$email->setType('email');
$email->setRequired(true);

$dto->addProperty($email);
```

### Nested Objects

```php
// Setting nested properties
$dto->address->street = '123 Main St';
$dto->address->city = 'New York';
$dto->address->state = 'NY';
$dto->address->zipCode = '10001';

// Accessing nested properties
$street = $dto->address->street;
$city = $dto->address->city;
```

## Validation

### Built-in Validators

The DTO component includes comprehensive validation for each property type:

```php
// Validate entire DTO
if( !$dto->validate() ) 
{
    $errors = $dto->getErrors();
    foreach( $errors as $property => $propertyErrors ) 
    {
        echo "Property '$property' has errors:\n";
        foreach( $propertyErrors as $error ) 
        {
            echo "  - $error\n";
        }
    }
}

// Validate specific property
$usernameProperty = $dto->getProperty('username');
if (!$usernameProperty->validate()) {
    $errors = $usernameProperty->getErrors();
}
```

### Validation Rules

#### Length Validation
```yaml
username:
  type: string
  length:
    min: 3
    max: 20
```

#### Range Validation
```yaml
age:
  type: integer
  range:
    min: 18
    max: 65
```

#### Pattern Validation
```yaml
phoneNumber:
  type: string
  pattern: '/^\+?[1-9]\d{1,14}$/'  # E.164 format
```

#### Enum Validation
```yaml
status:
  type: string
  enum: ['active', 'inactive', 'pending']
```

#### Custom Validation
```php
use Neuron\Validation\IValidator;

class CustomValidator implements IValidator
{
    public function validate($value): bool
    {
        // Custom validation logic
        return $value !== 'forbidden';
    }

    public function getError(): string
    {
        return 'Value cannot be "forbidden"';
    }
}

// Add to property
$property->addValidator(new CustomValidator());
```

## Data Mapping

### Mapper Configuration

Create a mapping configuration (`mapping.yaml`):

```yaml
map:
  # Simple mapping
  external.username: dto.username
  external.user_email: dto.email

  # Nested mapping
  external.user.profile.age: dto.age
  external.user.contact.street: dto.address.street
  external.user.contact.city: dto.address.city

  # Array mapping
  external.phones: dto.phoneNumbers
  external.phones.type: dto.phoneNumbers.type
  external.phones.value: dto.phoneNumbers.number
```

### Using the Mapper

```php
use Neuron\Dto\Mapper\Factory as MapperFactory;

// Create mapper
$mapperFactory = new MapperFactory('mapping.yaml');
$mapper = $mapperFactory->create();

// External data structure
$externalData = [
    'external' => [
        'username' => 'johndoe',
        'user_email' => 'john@example.com',
        'user' => [
            'profile' => [
                'age' => 30
            ],
            'contact' => [
                'street' => '123 Main St',
                'city' => 'New York'
            ]
        ],
        'phones' => [
            ['type' => 'mobile', 'value' => '+1234567890'],
            ['type' => 'home', 'value' => '+0987654321']
        ]
    ]
];

// Map to DTO
$mapper->map($dto, $externalData);

// Now DTO contains mapped data
echo $dto->username;  // 'johndoe'
echo $dto->address->street;  // '123 Main St'
echo $dto->phoneNumbers[0]->number;  // '+1234567890'
```

### Dynamic Mapping

```php
use Neuron\Dto\Mapper\Dynamic;

$mapper = new Dynamic();

// Define mappings programmatically
$mapper->addMapping('source.field1', 'target.property1');
$mapper->addMapping('source.nested.field2', 'target.property2');

// Map data
$mapper->map($dto, $sourceData);
```

## Property Types

### Supported Types

| Type | Description | Validation |
|------|-------------|------------|
| `string` | Text values | Length, pattern |
| `integer` | Whole numbers | Range, min, max |
| `float` | Decimal numbers | Range, precision |
| `boolean` | True/false values | Type checking |
| `array` | Lists of items | Item validation |
| `object` | Nested objects | Property validation |
| `email` | Email addresses | RFC compliance |
| `url` | URLs | URL format |
| `date` | Date values | Date format |
| `date_time` | Date and time | DateTime format |
| `time` | Time values | Time format |
| `currency` | Money amounts | Currency format |
| `uuid` | UUIDs | UUID v4 format |
| `ip_address` | IP addresses | IPv4/IPv6 |
| `phone_number` | Phone numbers | International format |
| `name` | Person names | Name validation |
| `ein` | EIN numbers | US EIN format |
| `upc` | UPC codes | UPC-A format |
| `numeric` | Any number | Numeric validation |

### Type Examples

```yaml
dto:
  # String with constraints
  username:
    type: string
    length:
      min: 3
      max: 20
    pattern: '/^[a-zA-Z0-9_]+$/'

  # Email validation
  email:
    type: email
    required: true

  # URL validation
  website:
    type: url
    required: false

  # Date with format
  birthDate:
    type: date
    format: 'Y-m-d'

  # Currency
  price:
    type: currency
    range:
      min: 0.01
      max: 999999.99

  # UUID
  userId:
    type: uuid
    required: true

  # IP Address
  clientIp:
    type: ip_address
    version: 4  # IPv4 only

  # Phone number
  phone:
    type: phone_number
    format: international
```

## Collections

### Array of Objects

```yaml
dto:
  users:
    type: array
    items:
      type: object
      properties:
        id:
          type: integer
          required: true
        name:
          type: string
          required: true
        email:
          type: email
          required: true
```

```php
// Adding items to collection
$dto->users[] = (object)[
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com'
];

// Accessing collection items
foreach ($dto->users as $user) {
    echo $user->name;
}

// Collection validation
$collection = new Collection($dto->users);
if (!$collection->validate()) {
    $errors = $collection->getErrors();
}
```

### Array of Primitives

```yaml
dto:
  tags:
    type: array
    items:
      type: string
      length:
        min: 1
        max: 20

  scores:
    type: array
    items:
      type: integer
      range:
        min: 0
        max: 100
```

## Advanced Usage

### Complex DTO Example

```yaml
dto:
  # User profile DTO
  profile:
    type: object
    properties:
      personalInfo:
        type: object
        required: true
        properties:
          firstName:
            type: string
            required: true
            length:
              min: 2
              max: 50
          lastName:
            type: string
            required: true
            length:
              min: 2
              max: 50
          dateOfBirth:
            type: date
            required: true
          gender:
            type: string
            enum: ['male', 'female', 'other', 'prefer_not_to_say']

      contactInfo:
        type: object
        required: true
        properties:
          emails:
            type: array
            items:
              type: object
              properties:
                type:
                  type: string
                  enum: ['personal', 'work']
                  required: true
                address:
                  type: email
                  required: true
                verified:
                  type: boolean
                  default: false

          phones:
            type: array
            items:
              type: object
              properties:
                type:
                  type: string
                  enum: ['mobile', 'home', 'work']
                number:
                  type: phone_number
                  required: true
                primary:
                  type: boolean
                  default: false

      preferences:
        type: object
        properties:
          newsletter:
            type: boolean
            default: true
          notifications:
            type: object
            properties:
              email:
                type: boolean
                default: true
              sms:
                type: boolean
                default: false
              push:
                type: boolean
                default: true

          language:
            type: string
            enum: ['en', 'es', 'fr', 'de']
            default: 'en'
```

### Custom DTO Class

```php
use Neuron\Dto\Dto;

class UserDto extends Dto
{
    public function __construct()
    {
        parent::__construct();
        $this->loadConfiguration('user.yaml');
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function isAdult(): bool
    {
        return $this->age >= 18;
    }

    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'email' => $this->email,
            'fullName' => $this->getFullName(),
            'isAdult' => $this->isAdult()
        ];
    }
}
```

### DTO Factory with Caching

```php
use Neuron\Dto\Factory;

class CachedDtoFactory extends Factory
{
    private static array $cache = [];

    public function create(): Dto
    {
        $cacheKey = md5($this->configPath);

        if (!isset(self::$cache[$cacheKey])) {
            self::$cache[$cacheKey] = parent::create();
        }

        // Return deep clone to prevent shared state
        return clone self::$cache[$cacheKey];
    }
}
```

## Testing

### Unit Testing DTOs

```php
use PHPUnit\Framework\TestCase;
use Neuron\Dto\Factory;

class DtoTest extends TestCase
{
    private $dto;

    protected function setUp(): void
    {
        $factory = new Factory('test-dto.yaml');
        $this->dto = $factory->create();
    }

    public function testValidation(): void
    {
        $this->dto->username = 'ab';  // Too short
        $this->dto->email = 'invalid-email';

        $this->assertFalse($this->dto->validate());

        $errors = $this->dto->getErrors();
        $this->assertArrayHasKey('username', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testValidData(): void
    {
        $this->dto->username = 'johndoe';
        $this->dto->email = 'john@example.com';
        $this->dto->age = 25;

        $this->assertTrue($this->dto->validate());
        $this->assertEmpty($this->dto->getErrors());
    }

    public function testNestedObjects(): void
    {
        $this->dto->address->street = '123 Main St';
        $this->dto->address->city = 'New York';

        $this->assertEquals('123 Main St', $this->dto->address->street);
        $this->assertEquals('New York', $this->dto->address->city);
    }

    public function testJsonExport(): void
    {
        $this->dto->username = 'johndoe';
        $this->dto->email = 'john@example.com';

        $json = $this->dto->getAsJson();
        $decoded = json_decode($json, true);

        $this->assertEquals('johndoe', $decoded['username']);
        $this->assertEquals('john@example.com', $decoded['email']);
    }
}
```

### Testing Mappers

```php
class MapperTest extends TestCase
{
    public function testDataMapping(): void
    {
        $factory = new Factory('dto.yaml');
        $dto = $factory->create();

        $mapperFactory = new MapperFactory('mapping.yaml');
        $mapper = $mapperFactory->create();

        $sourceData = [
            'external' => [
                'user_name' => 'johndoe',
                'user_email' => 'john@example.com'
            ]
        ];

        $mapper->map($dto, $sourceData);

        $this->assertEquals('johndoe', $dto->username);
        $this->assertEquals('john@example.com', $dto->email);
    }
}
```

## Best Practices

### DTO Design

```yaml
# Good: Clear, consistent naming
dto:
  firstName:
    type: string
    required: true
  lastName:
    type: string
    required: true
  emailAddress:
    type: email
    required: true

# Avoid: Inconsistent or unclear names
dto:
  fname:  # Too abbreviated
  last_name:  # Inconsistent style
  mail:  # Ambiguous
```

### Validation Strategy

```php
// Always validate before processing
if( !$dto->validate() ) 
{
    // Log errors
    Log::error('DTO validation failed', $dto->getErrors());

    // Return early with error response
    return new ValidationErrorResponse($dto->getErrors());
}

// Process valid data
$result = $service->process($dto);
```

### Error Handling

```php
try 
{
    $dto->username = $input['username'];
    $dto->email = $input['email'];

    if( !$dto->validate() ) 
    {
        throw new ValidationException($dto->getErrors());
    }

    $user = $userService->create($dto);

} 
catch( ValidationException $e ) 
{
    // Handle validation errors
    return response()->json([
        'error' => 'Validation failed',
        'details' => $e->getErrors()
    ], 422);

} 
catch (PropertyNotFound $e) 
{
    // Handle missing property
    return response()->json([
        'error' => 'Invalid property: ' . $e->getMessage()
    ], 400);
}
```

### Reusable DTOs

```php
// Base DTO for common properties
abstract class BaseDto extends Dto
{
    protected function addTimestamps(): void
    {
        $createdAt = new Property();
        $createdAt->setName('createdAt');
        $createdAt->setType('date_time');
        $this->addProperty($createdAt);

        $updatedAt = new Property();
        $updatedAt->setName('updatedAt');
        $updatedAt->setType('date_time');
        $this->addProperty($updatedAt);
    }
}

// Specific DTO extending base
class UserDto extends BaseDto
{
    public function __construct()
    {
        parent::__construct();
        $this->loadConfiguration('user.yaml');
        $this->addTimestamps();
    }
}
```

### Performance Optimization

```php
// Cache DTO definitions
class DtoCache
{
    private static array $definitions = [];

    public static function getDefinition(string $config): array
    {
        if (!isset(self::$definitions[$config])) {
            self::$definitions[$config] = Yaml::parseFile($config);
        }

        return self::$definitions[$config];
    }
}

// Use lazy loading for nested objects
class LazyDto extends Dto
{
    private array $lazyProperties = [];

    public function __get(string $name)
    {
        if( isset( $this->lazyProperties[ $name ] ) ) 
        {
            // Load only when accessed
            $this->loadProperty($name);
        }

        return parent::__get($name);
    }
}
```

## Integration Examples

### API Request Validation

```php
class ApiController
{
    private Factory $dtoFactory;

    public function createUser(Request $request): Response
    {
        $dto = $this->dtoFactory->create('user');

        // Map request data to DTO
        $mapper = new RequestMapper();
        $mapper->map($dto, $request->all());

        // Validate
        if( !$dto->validate() ) 
        {
            return response()->json([
                'errors' => $dto->getErrors()
            ], 422);
        }

        // Process valid data
        $user = $this->userService->create($dto);

        return response()->json($user, 201);
    }
}
```

### Database Integration

```php
class UserRepository
{
    public function save(UserDto $dto): User
    {
        $user = new User();

        $user->username = $dto->username;
        $user->email = $dto->email;
        $user->profile = json_encode([
            'firstName' => $dto->firstName,
            'lastName' => $dto->lastName,
            'address' => [
                'street' => $dto->address->street,
                'city' => $dto->address->city,
                'state' => $dto->address->state,
                'zipCode' => $dto->address->zipCode
            ]
        ]);

        $user->save();

        return $user;
    }

    public function toDto(User $user): UserDto
    {
        $factory = new Factory('user.yaml');
        $dto = $factory->create();

        $dto->username = $user->username;
        $dto->email = $user->email;

        $profile = json_decode($user->profile, true);
        $dto->firstName = $profile['firstName'];
        $dto->lastName = $profile['lastName'];
        $dto->address->street = $profile['address']['street'];
        $dto->address->city = $profile['address']['city'];

        return $dto;
    }
}
```

## More Information

- **Neuron Framework**: [neuronphp.com](http://neuronphp.com)
- **GitHub**: [github.com/neuron-php/dto](https://github.com/neuron-php/dto)
- **Packagist**: [packagist.org/packages/neuron-php/dto](https://packagist.org/packages/neuron-php/dto)

## License

MIT License - see LICENSE file for details
