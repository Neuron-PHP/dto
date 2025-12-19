<?php

namespace Test\Dto;

use Neuron\Dto\Dto;
use Neuron\Dto\Factory;
use PHPUnit\Framework\TestCase;

class DtoCompositionTest extends TestCase
{
	public function testLoadReferencedDto()
	{
		$Factory = new Factory( 'examples/composed-dto.yaml' );
		$Dto = $Factory->create();

		$this->assertIsObject( $Dto );

		// Check that the main DTO has the expected properties
		$this->assertArrayHasKey( 'id', $Dto->getProperties() );
		$this->assertArrayHasKey( 'title', $Dto->getProperties() );
		$this->assertArrayHasKey( 'timestamps', $Dto->getProperties() );
		$this->assertArrayHasKey( 'author', $Dto->getProperties() );
		$this->assertArrayHasKey( 'shippingAddress', $Dto->getProperties() );
	}

	public function testReferencedDtoType()
	{
		$Factory = new Factory( 'examples/composed-dto.yaml' );
		$Dto = $Factory->create();

		// Check that referenced properties have 'dto' type
		$this->assertEquals(
			'dto',
			$Dto->getProperty( 'timestamps' )->getType()
		);

		$this->assertEquals(
			'dto',
			$Dto->getProperty( 'author' )->getType()
		);

		$this->assertEquals(
			'dto',
			$Dto->getProperty( 'shippingAddress' )->getType()
		);
	}

	public function testReferencedDtoValue()
	{
		$Factory = new Factory( 'examples/composed-dto.yaml' );
		$Dto = $Factory->create();

		// Get the timestamps DTO
		$TimestampsDto = $Dto->getProperty( 'timestamps' )->getValue();
		$this->assertInstanceOf( Dto::class, $TimestampsDto );

		// Check that it has the expected properties from timestamps.yaml
		$this->assertArrayHasKey( 'createdAt', $TimestampsDto->getProperties() );
		$this->assertArrayHasKey( 'updatedAt', $TimestampsDto->getProperties() );
	}

	public function testReferencedDtoNesting()
	{
		$Factory = new Factory( 'examples/composed-dto.yaml' );
		$Dto = $Factory->create();

		// Get the author DTO
		$AuthorDto = $Dto->getProperty( 'author' )->getValue();
		$this->assertInstanceOf( Dto::class, $AuthorDto );

		// Check that it has the expected properties from user.yaml
		$this->assertArrayHasKey( 'id', $AuthorDto->getProperties() );
		$this->assertArrayHasKey( 'username', $AuthorDto->getProperties() );
		$this->assertArrayHasKey( 'email', $AuthorDto->getProperties() );
		$this->assertArrayHasKey( 'firstName', $AuthorDto->getProperties() );
		$this->assertArrayHasKey( 'lastName', $AuthorDto->getProperties() );
	}

	public function testReferencedDtoValidation()
	{
		$Factory = new Factory( 'examples/composed-dto.yaml' );
		$Dto = $Factory->create();

		// Set values on the referenced DTO
		$Dto->timestamps->createdAt = '2024-01-01 10:00:00';
		$Dto->timestamps->updatedAt = '2024-01-02 12:00:00';

		// Verify values were set
		$this->assertEquals( '2024-01-01 10:00:00', $Dto->timestamps->createdAt );
		$this->assertEquals( '2024-01-02 12:00:00', $Dto->timestamps->updatedAt );
	}

	public function testReferencedDtoCaching()
	{
		// Create two DTOs that reference the same timestamps.yaml
		$Factory1 = new Factory( 'examples/composed-dto.yaml' );
		$Dto1 = $Factory1->create();

		$Factory2 = new Factory( 'examples/composed-dto.yaml' );
		$Dto2 = $Factory2->create();

		// The DTOs should be separate instances (due to cloning)
		$this->assertNotSame(
			$Dto1->getProperty( 'timestamps' )->getValue(),
			$Dto2->getProperty( 'timestamps' )->getValue()
		);
	}

	public function testReferencedDtoWithRequiredFields()
	{
		$Factory = new Factory( 'examples/composed-dto.yaml' );
		$Dto = $Factory->create();

		// Check that required property is set correctly
		$this->assertTrue(
			$Dto->getProperty( 'timestamps' )->isRequired()
		);

		$this->assertTrue(
			$Dto->getProperty( 'author' )->isRequired()
		);

		$this->assertFalse(
			$Dto->getProperty( 'shippingAddress' )->isRequired()
		);
	}

	public function testReferencedAddressDto()
	{
		$Factory = new Factory( 'examples/composed-dto.yaml' );
		$Dto = $Factory->create();

		// Get the shippingAddress DTO
		$AddressDto = $Dto->getProperty( 'shippingAddress' )->getValue();
		$this->assertInstanceOf( Dto::class, $AddressDto );

		// Check that it has the expected properties from address.yaml
		$this->assertArrayHasKey( 'street', $AddressDto->getProperties() );
		$this->assertArrayHasKey( 'city', $AddressDto->getProperties() );
		$this->assertArrayHasKey( 'state', $AddressDto->getProperties() );
		$this->assertArrayHasKey( 'zipCode', $AddressDto->getProperties() );
	}

	public function testNestedDtoPropertyAccess()
	{
		$Factory = new Factory( 'examples/composed-dto.yaml' );
		$Dto = $Factory->create();

		// Set nested values
		$Dto->author->username = 'johndoe';
		$Dto->author->email = 'john@example.com';
		$Dto->author->firstName = 'John';
		$Dto->author->lastName = 'Doe';

		// Verify nested access works
		$this->assertEquals( 'johndoe', $Dto->author->username );
		$this->assertEquals( 'john@example.com', $Dto->author->email );
		$this->assertEquals( 'John', $Dto->author->firstName );
		$this->assertEquals( 'Doe', $Dto->author->lastName );
	}

	public function testMissingRefThrowsException()
	{
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "requires a 'ref' parameter" );

		// Create a DTO definition with 'dto' type but no 'ref'
		$Properties = [
			'name'    => [
				'type'     => 'string',
				'required' => true
			],
			'metadata' => [
				'type'     => 'dto',
				'required' => true
				// Missing 'ref' parameter
			]
		];

		$Factory = new Factory( $Properties );
		$Dto = $Factory->create();
	}
}
