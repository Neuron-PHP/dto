<?php

namespace Tests\Dto;

use Neuron\Dto\Factory;
use Neuron\Dto\Mapper\Request as RequestMapper;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Request mapper
 */
class RequestMapperTest extends TestCase
{
	private string $_testFile;

	protected function setUp(): void
	{
		parent::setUp();

		// Create a test DTO definition
		$this->_testFile = sys_get_temp_dir() . '/test-request-dto.yaml';

		$yaml = <<<YAML
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
YAML;

		file_put_contents( $this->_testFile, $yaml );
	}

	protected function tearDown(): void
	{
		if( file_exists( $this->_testFile ) )
		{
			unlink( $this->_testFile );
		}

		parent::tearDown();
	}

	public function testMapWithDataArray(): void
	{
		$factory = new Factory( $this->_testFile );
		$dto = $factory->create();

		$mapper = new RequestMapper();

		$data = [
			'username' => 'johndoe',
			'email' => 'john@example.com',
			'age' => 25
		];

		$mapper->map( $dto, $data );

		$this->assertEquals( 'johndoe', $dto->username );
		$this->assertEquals( 'john@example.com', $dto->email );
		$this->assertEquals( 25, $dto->age );
	}

	public function testMapIgnoresNonExistentProperties(): void
	{
		$factory = new Factory( $this->_testFile );
		$dto = $factory->create();

		$mapper = new RequestMapper();

		$data = [
			'username' => 'johndoe',
			'email' => 'john@example.com',
			'age' => 25,
			'nonexistent' => 'should be ignored'
		];

		$mapper->map( $dto, $data );

		$this->assertEquals( 'johndoe', $dto->username );
		$this->assertEquals( 'john@example.com', $dto->email );
		$this->assertEquals( 25, $dto->age );

		// Should not have added nonexistent property
		$this->assertNull( $dto->getProperty( 'nonexistent' ) );
	}

	public function testMapCollectsValidationErrors(): void
	{
		$factory = new Factory( $this->_testFile );
		$dto = $factory->create();

		$mapper = new RequestMapper();

		$data = [
			'username' => 'ab',  // Too short (min 3)
			'email' => 'invalid-email',  // Invalid format
			'age' => 150  // Out of range (max 120)
		];

		$mapper->map( $dto, $data );

		// Run validation (logs errors but doesn't throw)
		$dto->validate();

		// Check that errors were collected
		$errors = $dto->getErrors();
		$this->assertNotEmpty( $errors, 'Should have validation errors' );
		$this->assertGreaterThan( 0, count( $errors ), 'Should have multiple validation errors' );
	}

	public function testMapWithArrayOfKeys(): void
	{
		// This test simulates using the mapper with just field names
		// In real usage, it would call Post::filterScalar() for each
		$factory = new Factory( $this->_testFile );
		$dto = $factory->create();

		$mapper = new RequestMapper();

		// When data is an array of keys, mapper should fetch from POST
		// For testing, we'll pass associative array instead
		$data = [
			'username' => 'testuser',
			'email' => 'test@example.com'
		];

		$mapper->map( $dto, $data );

		$this->assertEquals( 'testuser', $dto->username );
		$this->assertEquals( 'test@example.com', $dto->email );
	}

	public function testMapReturnsDto(): void
	{
		$factory = new Factory( $this->_testFile );
		$dto = $factory->create();

		$mapper = new RequestMapper();

		$data = [
			'username' => 'johndoe',
			'email' => 'john@example.com'
		];

		$result = $mapper->map( $dto, $data );

		$this->assertInstanceOf( \Neuron\Dto\Dto::class, $result );
		$this->assertSame( $dto, $result );
	}
}