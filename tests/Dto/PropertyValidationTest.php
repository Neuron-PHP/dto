<?php

use Neuron\Dto\Factory;
use PHPUnit\Framework\TestCase;

class PropertyValidationTest extends TestCase
{
	public function testRequiredArrayPropertyWithEmptyRawArray()
	{
		// Create a DTO with a required array property
		$Properties = [
			'tags' => [
				'type'     => 'array',
				'required' => true
			]
		];

		$Factory = new Factory( $Properties );
		$Dto = $Factory->create();

		// Set value to an empty raw array and validate
		// Validation exception is thrown when validate() is called
		try {
			$Dto->tags = [];
			$Dto->validate();
			$this->fail( 'Expected validation exception' );
		} catch ( \Neuron\Core\Exceptions\Validation $e ) {
			$this->assertNotEmpty( $e->errors );
			$this->assertStringContainsString( 'array item is required', $e->errors[0] );
		}
	}

	public function testRequiredArrayPropertyWithNullValue()
	{
		// Create a DTO with a required array property
		$Properties = [
			'items' => [
				'type'     => 'array',
				'required' => true
			]
		];

		$Factory = new Factory( $Properties );
		$Dto = $Factory->create();

		// Set value to null and validate
		// Validation exception is thrown when validate() is called
		try {
			$Dto->items = null;
			$Dto->validate();
			$this->fail( 'Expected validation exception' );
		} catch ( \Neuron\Core\Exceptions\Validation $e ) {
			$this->assertNotEmpty( $e->errors );
			$this->assertStringContainsString( 'array item is required', $e->errors[0] );
		}
	}

	public function testRequiredArrayPropertyWithNonArrayValue()
	{
		// Create a DTO with a required array property
		$Properties = [
			'data' => [
				'type'     => 'array',
				'required' => true
			]
		];

		$Factory = new Factory( $Properties );
		$Dto = $Factory->create();

		// Set value to a string (not an array or Collection) and validate
		// Validation exception is thrown when validate() is called
		try {
			$Dto->data = 'not_an_array';
			$Dto->validate();
			$this->fail( 'Expected validation exception' );
		} catch ( \Neuron\Core\Exceptions\Validation $e ) {
			$this->assertNotEmpty( $e->errors );
			$this->assertStringContainsString( 'array item is required', $e->errors[0] );
		}
	}
}
