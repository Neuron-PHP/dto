<?php

namespace Dto;

use Neuron\Dto\Factory;
use PHPUnit\Framework\TestCase;

class DtoFactoryTest extends TestCase
{

	public function testCreate()
	{
		$Factory = new Factory( 'examples/test.yaml' );

		$Dto = $Factory->create();

		$this->assertIsObject( $Dto );

		$this->assertEquals(
			'array',
			$Dto->getProperty( 'inventory' )
				 ->getType()
		);

		$this->assertEquals(
			$Dto,
			$Dto->getProperty( 'address' )
				 ->getValue()
				 ->getParent()
		);
	}

	public function testCreateFromArray()
	{
		$Properties = [
			'username' => [
				'type'     => 'string',
				'required' => true,
				'length'   => [
					'min' => 3,
					'max' => 20
				]
			],
			'email'    => [
				'type'     => 'email',
				'required' => true
			],
			'age'      => [
				'type'  => 'integer',
				'range' => [
					'min' => 18,
					'max' => 100
				]
			]
		];

		$Factory = new Factory( $Properties );

		$Dto = $Factory->create();

		$this->assertIsObject( $Dto );

		$this->assertArrayHasKey(
			'username',
			$Dto->getProperties()
		);

		$this->assertArrayHasKey(
			'email',
			$Dto->getProperties()
		);

		$this->assertArrayHasKey(
			'age',
			$Dto->getProperties()
		);

		$UsernameProperty = $Dto->getProperty( 'username' );

		$this->assertEquals(
			'string',
			$UsernameProperty->getType()
		);

		$this->assertTrue(
			$UsernameProperty->isRequired()
		);
	}

	public function testCreateFromArrayWithNestedObject()
	{
		$Properties = [
			'name'    => [
				'type'     => 'string',
				'required' => true
			],
			'address' => [
				'type'       => 'object',
				'required'   => true,
				'properties' => [
					'street' => [
						'type'     => 'string',
						'required' => true,
						'length'   => [
							'min' => 5,
							'max' => 100
						]
					],
					'city'   => [
						'type'     => 'string',
						'required' => true
					]
				]
			]
		];

		$Factory = new Factory( $Properties );

		$Dto = $Factory->create();

		$this->assertIsObject( $Dto );

		// Verify 'name' property exists and wasn't consumed as DTO name
		$this->assertArrayHasKey(
			'name',
			$Dto->getProperties()
		);

		$AddressProperty = $Dto->getProperty( 'address' );

		$this->assertEquals(
			'object',
			$AddressProperty->getType()
		);

		$AddressDto = $AddressProperty->getValue();

		$this->assertIsObject( $AddressDto );

		$this->assertArrayHasKey(
			'street',
			$AddressDto->getProperties()
		);

		$this->assertArrayHasKey(
			'city',
			$AddressDto->getProperties()
		);
	}

	public function testGetSourceFromYamlFile()
	{
		$Factory = new Factory( 'examples/test.yaml' );

		$source = $Factory->getSource();

		$this->assertIsString( $source );
		$this->assertEquals( 'examples/test.yaml', $source );
	}

	public function testGetSourceFromArray()
	{
		$Properties = [
			'username' => [
				'type'     => 'string',
				'required' => true
			]
		];

		$Factory = new Factory( $Properties );

		$source = $Factory->getSource();

		$this->assertIsArray( $source );
		$this->assertEquals( $Properties, $source );
	}

	public function testCreateFromStructuredArrayWithName()
	{
		// Structured format with 'name' and 'properties' keys
		$StructuredArray = [
			'name'       => 'UserDto',
			'properties' => [
				'username' => [
					'type'     => 'string',
					'required' => true
				],
				'email'    => [
					'type'     => 'email',
					'required' => true
				]
			]
		];

		$Factory = new Factory( $StructuredArray );

		$Dto = $Factory->create();

		$this->assertIsObject( $Dto );

		// Verify properties were created from 'properties' key
		$this->assertArrayHasKey(
			'username',
			$Dto->getProperties()
		);

		$this->assertArrayHasKey(
			'email',
			$Dto->getProperties()
		);

		// Verify 'name' was not treated as a property
		$this->assertArrayNotHasKey(
			'name',
			$Dto->getProperties()
		);
	}
}
