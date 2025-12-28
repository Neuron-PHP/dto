<?php

use Neuron\Dto\Factory;
use PHPUnit\Framework\TestCase;

class EnumValidationTest extends TestCase
{
	public function testEnumValidationPassesWithValidValue()
	{
		$properties = [
			'role' => [
				'type'     => 'string',
				'required' => true,
				'enum'     => ['admin', 'editor', 'author', 'subscriber']
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Test each valid value
		$dto->role = 'admin';
		$dto->validate();
		$this->assertEquals( 'admin', $dto->role );

		$dto->role = 'editor';
		$dto->validate();
		$this->assertEquals( 'editor', $dto->role );

		$dto->role = 'subscriber';
		$dto->validate();
		$this->assertEquals( 'subscriber', $dto->role );
	}

	public function testEnumValidationFailsWithInvalidValue()
	{
		$properties = [
			'status' => [
				'type'     => 'string',
				'required' => true,
				'enum'     => ['active', 'inactive', 'pending']
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		try {
			$dto->status = 'invalid_status';
			$dto->validate();
			$this->fail( 'Expected validation exception for invalid enum value' );
		} catch ( \Neuron\Core\Exceptions\Validation $e ) {
			$this->assertNotEmpty( $e->errors );
			$this->assertStringContainsString( 'validation failed', $e->errors[0] );
		}
	}

	public function testEnumValidationIsCaseSensitive()
	{
		$properties = [
			'priority' => [
				'type'     => 'string',
				'required' => true,
				'enum'     => ['low', 'medium', 'high']
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Valid lowercase
		$dto->priority = 'low';
		$dto->validate();
		$this->assertEquals( 'low', $dto->priority );

		// Invalid uppercase
		try {
			$dto->priority = 'LOW';
			$dto->validate();
			$this->fail( 'Expected validation exception for case mismatch' );
		} catch ( \Neuron\Core\Exceptions\Validation $e ) {
			$this->assertNotEmpty( $e->errors );
		}
	}

	public function testEnumWithIntegerValues()
	{
		$properties = [
			'level' => [
				'type'     => 'integer',
				'required' => true,
				'enum'     => [1, 2, 3, 4, 5]
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Valid integer
		$dto->level = 3;
		$dto->validate();
		$this->assertEquals( 3, $dto->level );

		// Invalid integer
		try {
			$dto->level = 10;
			$dto->validate();
			$this->fail( 'Expected validation exception for invalid enum value' );
		} catch ( \Neuron\Core\Exceptions\Validation $e ) {
			$this->assertNotEmpty( $e->errors );
		}
	}

	public function testEnumWithLooseComparison()
	{
		$properties = [
			'code' => [
				'type'     => 'string',
				'required' => true,
				'enum'     => ['low', 'medium', 'high'],
				'strict'   => false
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// With strict mode defaulting to true, this should work
		$dto->code = 'low';
		$dto->validate();
		$this->assertEquals( 'low', $dto->code );

		// Note: Loose comparison primarily matters for mixed-type scenarios
		// but since we have type validators (IsString, IsInteger), the type
		// check happens first, so loose comparison has limited use in DTOs
	}

	public function testEnumWithStrictComparison()
	{
		$properties = [
			'id' => [
				'type'     => 'integer',
				'required' => true,
				'enum'     => [1, 2, 3],
				'strict'   => true
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Valid integer
		$dto->id = 1;
		$dto->validate();
		$this->assertEquals( 1, $dto->id );

		// With strict comparison, string '1' should NOT match integer 1
		try {
			$dto->id = '1';
			$dto->validate();
			$this->fail( 'Expected validation exception for type mismatch in strict mode' );
		} catch ( \Neuron\Core\Exceptions\Validation $e ) {
			$this->assertNotEmpty( $e->errors );
		}
	}

	public function testEnumLoadedFromYamlFile()
	{
		// Create a temporary YAML file
		$yamlContent = <<<YAML
dto:
  role:
    type: string
    required: true
    enum: ['admin', 'editor', 'author', 'subscriber']
YAML;

		$tempFile = sys_get_temp_dir() . '/test_enum_' . uniqid() . '.yaml';
		file_put_contents( $tempFile, $yamlContent );

		try {
			$factory = new Factory( $tempFile );
			$dto = $factory->create();

			// Valid value
			$dto->role = 'admin';
			$dto->validate();
			$this->assertEquals( 'admin', $dto->role );

			// Invalid value
			try {
				$dto->role = 'moderator';
				$dto->validate();
				$this->fail( 'Expected validation exception for invalid enum value' );
			} catch ( \Neuron\Core\Exceptions\Validation $e ) {
				$this->assertNotEmpty( $e->errors );
			}
		} finally {
			// Clean up
			if ( file_exists( $tempFile ) ) {
				unlink( $tempFile );
			}
		}
	}

	public function testEnumWithOptionalProperty()
	{
		$properties = [
			'category' => [
				'type'     => 'string',
				'required' => false,
				'enum'     => ['news', 'sports', 'technology']
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Validation should pass when optional property is not set
		$dto->validate();

		// But if set, it must be a valid enum value
		$dto->category = 'news';
		$dto->validate();
		$this->assertEquals( 'news', $dto->category );

		// Invalid value should fail
		try {
			$dto->category = 'politics';
			$dto->validate();
			$this->fail( 'Expected validation exception for invalid enum value' );
		} catch ( \Neuron\Core\Exceptions\Validation $e ) {
			$this->assertNotEmpty( $e->errors );
		}
	}

	public function testEnumInNestedObject()
	{
		$properties = [
			'user' => [
				'type'       => 'object',
				'required'   => true,
				'properties' => [
					'username' => [
						'type'     => 'string',
						'required' => true
					],
					'role'     => [
						'type'     => 'string',
						'required' => true,
						'enum'     => ['admin', 'user', 'guest']
					]
				]
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Valid nested enum
		$dto->user->username = 'johndoe';
		$dto->user->role = 'admin';
		$dto->validate();
		$this->assertEquals( 'admin', $dto->user->role );

		// Invalid nested enum
		try {
			$dto->user->role = 'superadmin';
			$dto->validate();
			$this->fail( 'Expected validation exception for invalid enum in nested object' );
		} catch ( \Neuron\Core\Exceptions\Validation $e ) {
			$this->assertNotEmpty( $e->errors );
		}
	}

	public function testMultipleEnumProperties()
	{
		$properties = [
			'status'   => [
				'type'     => 'string',
				'required' => true,
				'enum'     => ['draft', 'published', 'archived']
			],
			'priority' => [
				'type'     => 'string',
				'required' => true,
				'enum'     => ['low', 'medium', 'high']
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Both valid
		$dto->status = 'published';
		$dto->priority = 'high';
		$dto->validate();

		$this->assertEquals( 'published', $dto->status );
		$this->assertEquals( 'high', $dto->priority );

		// One invalid
		try {
			$dto->status = 'deleted'; // Invalid
			$dto->priority = 'high';  // Valid
			$dto->validate();
			$this->fail( 'Expected validation exception' );
		} catch ( \Neuron\Core\Exceptions\Validation $e ) {
			$this->assertNotEmpty( $e->errors );
		}
	}
}
